<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider; 
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(TransactionRepository::class, function ($app) {
            return new TransactionRepository();
        });

        // Bind the UserRepository interface to its implementation
        $this->app->singleton(UserRepository::class, function ($app) {
            return new UserRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
