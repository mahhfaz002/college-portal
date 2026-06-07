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
        // Register one Gate per capability from the central Permissions map,
        // so Blade can use @can('manage_fees') etc. and routes/controllers can
        // authorize() against the same single source of truth.
        foreach (array_keys(Permissions::MATRIX) as $capability) {
            Gate::define($capability, fn ($user) => Permissions::roleCan($user->role, $capability));
        }

        // Make school branding available to every view as $school.
        View::composer('*', function ($view) {
            $view->with('school', [
                'name'     => setting('school_name', config('app.name', 'School Portal')),
                'tagline'  => setting('school_tagline', ''),
                'logo'     => setting('school_logo'),
                'color'    => setting('primary_color', '#2563eb'),
                'currency' => setting('currency_symbol', '₦'),
                'term'     => setting('current_term', ''),
                'session'  => setting('current_session', ''),
            ]);
        });
    }
}
