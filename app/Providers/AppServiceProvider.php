<?php

namespace App\Providers;

use App\Filesystem\VercelBlobAdapter;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Policies\RegistrationDocumentPolicy;
use App\Policies\RegistrationPolicy;
use App\Support\SettingsRepository;
use App\Support\Vercel\BlobClient;
use App\View\Composers\ApplicantComposer;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem as Flysystem;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'Indonesian');
        CarbonImmutable::setLocale('id');

        $this->registerVercelBlobDriver();
        $this->registerRateLimiters();
        $this->registerPolicies();
        $this->shareViewData();
    }

    /**
     * Vercel gives a function a read-only filesystem, so on that host the
     * "public" and "private" disks point at a Vercel Blob store instead of at
     * storage/app. Selected per disk through PUBLIC_DISK_DRIVER and
     * PRIVATE_DISK_DRIVER, which stay unset -- and so local -- everywhere else.
     */
    private function registerVercelBlobDriver(): void
    {
        Storage::extend('vercel_blob', function ($app, array $config): LaravelFilesystemAdapter {
            $access = $config['access'] ?? 'private';

            $adapter = new VercelBlobAdapter(
                new BlobClient((string) ($config['token'] ?? ''), $access),
                $access,
            );

            return new LaravelFilesystemAdapter(new Flysystem($adapter, $config), $adapter, $config);
        });
    }

    private function registerRateLimiters(): void
    {
        [$statusAttempts, $statusMinutes] = $this->parseLimit(config('ppdb.rate_limit.status_check'));
        [$loginAttempts, $loginMinutes] = $this->parseLimit(config('ppdb.rate_limit.admin_login'));

        // One sign-in serves everyone, so the stricter of the two configured
        // limits applies to it.
        //
        // Keyed by account *and* IP rather than IP alone: a school sits behind
        // one public address, so an IP-only key would let one person's mistyped
        // password lock out every other applicant and the whole committee. The
        // per-IP ceiling is kept as a second, looser limit so the endpoint is
        // still not an open door for credential stuffing.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinutes(max($statusMinutes, $loginMinutes), min($statusAttempts, $loginAttempts))
                ->by('login:'.Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinutes(max($statusMinutes, $loginMinutes), min($statusAttempts, $loginAttempts) * 6)
                ->by('login-ip:'.$request->ip()),
        ]);

        RateLimiter::for('status-check', fn (Request $request) => Limit::perMinutes($statusMinutes, $statusAttempts)
            ->by($request->ip()));

        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinutes($loginMinutes, $loginAttempts)
            ->by($request->ip()));

        RateLimiter::for('document-download', fn (Request $request) => Limit::perMinute(60)
            ->by($request->ip()));
    }

    private function registerPolicies(): void
    {
        Gate::policy(Registration::class, RegistrationPolicy::class);
        Gate::policy(RegistrationDocument::class, RegistrationDocumentPolicy::class);
    }

    /**
     * School identity is needed by every layout, so it is shared globally
     * rather than passed from each controller.
     */
    private function shareViewData(): void
    {
        View::composer('*', function ($view): void {
            $view->with('settings', $this->app->make(SettingsRepository::class));
        });

        View::composer(
            ['layouts.applicant', 'partials.applicant-sidebar', 'partials.applicant-topbar'],
            ApplicantComposer::class
        );
    }

    /**
     * Parse an "attempts,minutes" config string.
     *
     * @return array{int, int}
     */
    private function parseLimit(string $value): array
    {
        $parts = array_map('intval', explode(',', $value));

        return [
            max($parts[0] ?? 5, 1),
            max($parts[1] ?? 1, 1),
        ];
    }
}
