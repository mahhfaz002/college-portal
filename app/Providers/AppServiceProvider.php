<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use App\Support\Permissions;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS for generated URLs in production (Laravel Cloud terminates
        // TLS at the edge); keeps assets/links/redirects on https.
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register one Gate per capability from the central Permissions map,
        // so Blade can use @can('manage_fees') etc. and routes/controllers can
        // authorize() against the same single source of truth.
        foreach (array_keys(Permissions::MATRIX) as $capability) {
            Gate::define($capability, fn ($user) => Permissions::roleCan($user->role, $capability));
        }

        // Make college branding available to every view as $school.
        // Prefer the logged-in user's college record so each registered college
        // brands its own portal; fall back to global settings / app name.
        View::composer('*', function ($view) {
            $college = function_exists('current_college') ? current_college() : null;
            $view->with('school', [
                // Neutral when no college is resolved (platform / super-admin pages).
                'name'     => $college?->name ?? 'College Portal',
                'tagline'  => $college?->tagline ?? '',
                'logo'     => $college?->logo_path,
                'color'    => $college?->primary_color ?? '#4F46E5',
                'currency' => setting('currency_symbol', '₦'),
                'term'     => setting('current_term', ''),
                'session'  => setting('current_session', ''),
            ]);
        });
    }
}
