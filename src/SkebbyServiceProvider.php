<?php

namespace alagaccia\skebby;

use Illuminate\Support\ServiceProvider;

/**
 * Skebby SMS Service Provider
 * 
 * This service provider registers the Skebby SMS service with Laravel's
 * service container and handles configuration publishing.
 */
class SkebbyServiceProvider extends ServiceProvider
{
    /**
     * Register services with the application container
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration with application configuration
        $this->mergeConfigFrom(__DIR__.'/config/skebby.php', 'skebby');
        
        // Register the Skebby service as a singleton
        $this->app->singleton('skebby', function ($app) {
            return new Skebby();
        });

        // Register the service with a more descriptive binding
        $this->app->bind(Skebby::class, function ($app) {
            return $app->make('skebby');
        });
    }

    /**
     * Bootstrap services and publish configuration files
     *
     * @return void
     */
    public function boot(): void
    {
        // Only register publishable assets when running in console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/skebby.php' => config_path('skebby.php'),
            ], 'skebby-config');

            // Optional: Add custom artisan commands here if needed
            // $this->commands([
            //     Commands\SkebbyTestCommand::class,
            // ]);
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides(): array
    {
        return ['skebby', Skebby::class];
    }
}