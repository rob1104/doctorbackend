<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        Schema::defaultStringLength(191);

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Login $event) {
            \Spatie\Activitylog\Facades\Activity::causedBy($event->user)
                ->event('login')
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Inicio de sesión exitoso');
        });

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Failed $event) {
            \Spatie\Activitylog\Facades\Activity::event('login_failed')
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'email' => $event->credentials['email'] ?? 'unknown',
                ])
                ->log('Intento de inicio de sesión fallido');
        });
    }
}
