<?php

namespace App\Support\Vercel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal REST client for Vercel Blob.
 *
 * Vercel publishes no PHP SDK, so this speaks the same HTTP API its JavaScript
 * SDK does. `x-api-version` is what pins that contract: bump it deliberately
 * after checking the SDK, never to make a failing call pass.
 *
 * A store is either public or private and cannot be switched afterwards, so an
 * instance is bound to one store through its read-write token. The token
 * carries the store id as its fourth underscore-separated part, which is also
 * the subdomain blobs are served from.
 */
class BlobClient
{
    private const API = 'https://blob.vercel-storage.com';

    private const API_VERSION = '12';

    private readonly string $storeId;

    /**
     * @param  string  $access  'public' or 'private'
     */
    public function __construct(
        private readonly string $token,
        private readonly string $access,
        private readonly int $timeout = 30,
    ) {
        $parts = explode('_', $this->token);

        if (count($parts) < 5 || $parts[3] === '') {
            throw new RuntimeException('Malformed Vercel Blob token: expected vercel_blob_rw_<storeId>_<secret>.');
        }

        $this->storeId = $parts[3];
    }

    /**
     * Public URL of a blob. For a private store the URL resolves only with a
     * bearer token, so it is an identifier rather than something to hand out.
     */
    public function url(string $pathname): string
    {
        $encoded = implode('/', array_map('rawurlencode', explode('/', $pathname)));

        return sprintf('https://%s.%s.blob.vercel-storage.com/%s', strtolower($this->storeId), $this->access, $encoded);
    }

    /**
     * @return array<string, mixed> the blob metadata returned by the API
     */
    public function put(string $pathname, string $contents, ?string $contentType = null, bool $overwrite = true): array
    {
        $headers = [
            'x-vercel-blob-access' => $this->access,
            // Pathnames are chosen by the caller and are already unique, so the
            // random suffix would only make them unpredictable to look up.
            'x-add-random-suffix' => '0',
            'x-allow-overwrite' => $overwrite ? '1' : '0',
        ];

        if ($contentType !== null && $contentType !== '') {
            $headers['x-content-type'] = $contentType;
        }

        $response = $this->request('PUT', '/?'.http_build_query(['pathname' => $pathname]), $headers, $contents);

        return (array) $response->json();
    }

    /**
     * Raw bytes of a blob, or null when it does not exist.
     */
    public function get(string $pathname): ?string
    {
        $request = Http::timeout($this->timeout)->retry(2, 200, throw: false);

        if ($this->access === 'private') {
            $request = $request->withToken($this->token);
        }

        $response = $request->get($this->url($pathname));

        if ($response->status() === 404) {
            return null;
        }

        $this->throwUnlessSuccessful($response, 'read '.$pathname);

        return $response->body();
    }

    /**
     * Blob metadata, or null when it does not exist.
     *
     * @return array<string, mixed>|null
     */
    public function head(string $pathname): ?array
    {
        // HEAD cannot carry a response body, so the API exposes this over GET.
        $response = $this->request('GET', '/?'.http_build_query(['url' => $pathname]), allowNotFound: true);

        if ($response->status() === 404) {
            return null;
        }

        return (array) $response->json();
    }

    /**
     * @param  array<int, string>  $pathnames
     */
    public function delete(array $pathnames): void
    {
        if ($pathnames === []) {
            return;
        }

        $this->request(
            'POST',
            '/delete',
            ['content-type' => 'application/json'],
            json_encode(['urls' => array_values($pathnames)], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * One page of blobs under a prefix.
     *
     * @return array{blobs: array<int, array<string, mixed>>, cursor: ?string, hasMore: bool}
     */
    public function list(string $prefix = '', ?string $cursor = null, int $limit = 1000): array
    {
        $query = ['limit' => (string) $limit];

        if ($prefix !== '') {
            $query['prefix'] = $prefix;
        }

        if ($cursor !== null) {
            $query['cursor'] = $cursor;
        }

        $payload = (array) $this->request('GET', '/?'.http_build_query($query))->json();

        return [
            'blobs' => $payload['blobs'] ?? [],
            'cursor' => $payload['cursor'] ?? null,
            'hasMore' => (bool) ($payload['hasMore'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function copy(string $from, string $to, ?string $contentType = null): array
    {
        $headers = ['x-vercel-blob-access' => $this->access];

        if ($contentType !== null && $contentType !== '') {
            $headers['x-content-type'] = $contentType;
        }

        $response = $this->request(
            'PUT',
            '/?'.http_build_query(['pathname' => $to, 'fromUrl' => $from]),
            $headers,
        );

        return (array) $response->json();
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function request(string $method, string $path, array $headers = [], ?string $body = null, bool $allowNotFound = false): Response
    {
        $request = Http::timeout($this->timeout)
            ->retry(2, 200, throw: false)
            ->withToken($this->token)
            ->withHeaders($headers + [
                'x-api-version' => self::API_VERSION,
                // The store id is not encoded in the bearer token when OIDC is
                // used, so the API expects it as its own header.
                'x-vercel-blob-store-id' => $this->storeId,
            ]);

        if ($body !== null) {
            $request = $request->withBody($body, $headers['content-type'] ?? 'application/octet-stream');
        }

        $response = $request->send($method, self::API.$path);

        if ($allowNotFound && $response->status() === 404) {
            return $response;
        }

        $this->throwUnlessSuccessful($response, strtolower($method).' '.$path);

        return $response;
    }

    private function throwUnlessSuccessful(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message') ?? $response->body();

        throw new RuntimeException(sprintf(
            'Vercel Blob request failed (%s): HTTP %d %s',
            $action,
            $response->status(),
            is_string($message) ? $message : '',
        ));
    }
}
