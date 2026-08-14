<?php

namespace App\Providers;

use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Policies\RegistrationDocumentPolicy;
use App\Policies\RegistrationPolicy;
use App\Support\SettingsRepository;
use App\View\Composers\ApplicantComposer;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        $this->registerRateLimiters();
        $this->registerPolicies();
        $this->shareViewData();
    }

    private function registerRateLimiters(): void
    {
        [$statusAttempts, $statusMinutes] = $this->parseLimit(config('ppdb.rate_limit.status_check'));
        [$loginAttempts, $loginMinutes] = $this->parseLimit(config('ppdb.rate_limit.admin_login'));

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
