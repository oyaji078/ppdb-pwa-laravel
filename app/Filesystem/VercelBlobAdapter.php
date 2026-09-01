<?php

namespace App\Filesystem;

use App\Support\Vercel\BlobClient;
use DateTimeImmutable;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Throwable;

/**
 * Flysystem adapter over Vercel Blob.
 *
 * Blob is a flat key/value store: a "directory" is only a slash inside a
 * pathname, so directory operations here are emulated by prefix. Visibility is
 * a property of the store rather than of an object, which is why the
 * application keeps two of them: a public store for CMS images and a private
 * store for applicant documents.
 */
class VercelBlobAdapter implements FilesystemAdapter
{
    public function __construct(
        private readonly BlobClient $client,
        private readonly string $access,
    ) {}

    public function fileExists(string $path): bool
    {
        return $this->client->head($path) !== null;
    }

    public function directoryExists(string $path): bool
    {
        return $this->client->list(rtrim($path, '/').'/', limit: 1)['blobs'] !== [];
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->put($path, $contents, $this->contentType($path, $config));
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $body = stream_get_contents($contents);

        if ($body === false) {
            throw UnableToWriteFile::atLocation($path, 'Could not read the given stream.');
        }

        $this->write($path, $body, $config);
    }

    public function read(string $path): string
    {
        try {
            $contents = $this->client->get($path);
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        if ($contents === null) {
            throw UnableToReadFile::fromLocation($path, 'Blob not found.');
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');

        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        $this->client->delete([$path]);
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = rtrim($path, '/').'/';
        $cursor = null;

        do {
            $page = $this->client->list($prefix, $cursor);

            $this->client->delete(array_column($page['blobs'], 'pathname'));

            $cursor = $page['cursor'];
        } while ($page['hasMore'] && $cursor !== null);
    }

    /**
     * Blob has no standalone directories: a folder exists exactly as long as
     * something inside it does, so there is nothing to create.
     */
    public function createDirectory(string $path, Config $config): void {}

    public function setVisibility(string $path, string $visibility): void
    {
        if ($visibility !== $this->visibilityOfStore()) {
            throw UnableToSetVisibility::atLocation(
                $path,
                'A Vercel Blob store is created public or private and cannot be changed per file.',
            );
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: $this->visibilityOfStore());
    }

    public function mimeType(string $path): FileAttributes
    {
        $metadata = $this->metadata($path);

        return new FileAttributes($path, mimeType: $metadata['contentType'] ?? null);
    }

    public function lastModified(string $path): FileAttributes
    {
        $metadata = $this->metadata($path);
        $uploadedAt = $metadata['uploadedAt'] ?? null;

        return new FileAttributes($path, lastModified: $uploadedAt
            ? (new DateTimeImmutable($uploadedAt))->getTimestamp()
            : null);
    }

    public function fileSize(string $path): FileAttributes
    {
        $metadata = $this->metadata($path);

        return new FileAttributes($path, fileSize: isset($metadata['size']) ? (int) $metadata['size'] : null);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $path === '' ? '' : rtrim($path, '/').'/';
        $cursor = null;
        $seenDirectories = [];

        do {
            $page = $this->client->list($prefix, $cursor);

            foreach ($page['blobs'] as $blob) {
                $pathname = $blob['pathname'] ?? null;

                if ($pathname === null) {
                    continue;
                }

                $relative = substr($pathname, strlen($prefix));

                if (! $deep && str_contains($relative, '/')) {
                    // A shallow listing reports the child folder once instead
                    // of everything buried under it.
                    $directory = $prefix.substr($relative, 0, (int) strpos($relative, '/'));

                    if (! isset($seenDirectories[$directory])) {
                        $seenDirectories[$directory] = true;

                        yield new DirectoryAttributes($directory);
                    }

                    continue;
                }

                yield new FileAttributes(
                    $pathname,
                    isset($blob['size']) ? (int) $blob['size'] : null,
                    $this->visibilityOfStore(),
                    isset($blob['uploadedAt']) ? (new DateTimeImmutable($blob['uploadedAt']))->getTimestamp() : null,
                    $blob['contentType'] ?? null,
                );
            }

            $cursor = $page['cursor'];
        } while ($page['hasMore'] && $cursor !== null);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->copy($this->client->url($source), $destination, $this->contentType($destination, $config));
        } catch (Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * Laravel resolves Storage::url() through this method.
     */
    public function getUrl(string $path): string
    {
        return $this->client->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(string $path): array
    {
        try {
            $metadata = $this->client->head($path);
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', $e->getMessage(), $e);
        }

        if ($metadata === null) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', 'Blob not found.');
        }

        return $metadata;
    }

    private function contentType(string $path, Config $config): ?string
    {
        $mimeType = $config->get('mimetype');

        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }

        return match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => null,
        };
    }

    private function visibilityOfStore(): string
    {
        return $this->access === 'public' ? Visibility::PUBLIC : Visibility::PRIVATE;
    }
}
