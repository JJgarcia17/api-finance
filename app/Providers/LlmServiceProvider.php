<?php

namespace App\Providers;

use App\Contracts\Llm\LlmClientInterface;
use App\Services\Llm\LlmClientFactory;
use Illuminate\Support\ServiceProvider;

class LlmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LlmClientInterface::class, function ($app) {
            return LlmClientFactory::create();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../../config/llm.php' => config_path('llm.php'),
        ], 'config');
    }
}
