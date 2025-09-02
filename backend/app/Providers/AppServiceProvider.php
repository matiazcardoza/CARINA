<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $providers = config('person.providers', []);
        if (!empty($providers)) {
            $this->app->tag($providers, 'person.providers');
        }

        $this->app->bind(\App\Services\PersonFinder::class, function ($app) {
            $tagged = $app->tagged('person.providers'); // iterable en el orden del config
            return new \App\Services\PersonFinder($tagged);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
