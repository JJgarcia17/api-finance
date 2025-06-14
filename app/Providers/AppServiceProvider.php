<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Financial Chat Services
        $this->app->singleton(\App\Services\FinancialChat\FinancialChatService::class);
        $this->app->singleton(\App\Services\FinancialChat\FinancialContextService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
