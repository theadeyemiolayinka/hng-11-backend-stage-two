<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class APIServiceProvider extends ServiceProvider
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

        Route::middleware(['api', \App\Http\Middleware\ForceJSON::class])
            ->name('api.')
            ->group(base_path('routes/api/auth.php'))
            ->group(base_path('routes/api/api.php'));

        Route::get("/api/misc/log", function () {
            return response()->file(storage_path('logs/laravel.log'));
        });

        Route::get("/api/misc/keep-alive", function () {
            return response()->json(['message' => 'I am alive!']);
        });
    }
}
