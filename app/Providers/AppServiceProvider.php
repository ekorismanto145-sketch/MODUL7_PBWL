<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Soal 3: Gate kelola-buku yang hanya mengizinkan role admin dan petugas
        Gate::define('kelola-buku', function (User $user): bool {
            return $user->hasRole(['admin', 'petugas']);
        });
    }
}
