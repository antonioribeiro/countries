<?php

namespace PragmaRX\Countries;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Configure package paths.
     */
    private function configurePaths()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('countries.php'),
        ]);

        $this->publishes([
            __DIR__.'/views/' => resource_path('views/vendor/pragmarx/countries/'),
        ]);
    }

    /**
     * Configure package folder views.
     */
    private function configureViews()
    {
        $this->loadViewsFrom(realpath(__DIR__.'/views'), 'pragmarx/countries');
    }

    /**
     * Merge configuration.
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/config.php', 'countries'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configurePaths();

        $this->mergeConfig();

        $this->configureViews();

        $this->registerService();
    }

    private function registerService()
    {
        $this->app->singleton('pragmarx.countries', function () {
            return new Service();
        });
    }
}
