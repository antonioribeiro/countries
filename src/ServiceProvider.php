<?php

namespace PragmaRX\Countries;

use PragmaRX\Countries\Support\CountriesRepository;
use PragmaRX\Countries\Support\CurrenciesRepository;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use PragmaRX\Countries\Support\Hydrator;

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
    protected function configurePaths()
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
    protected function configureViews()
    {
        $this->loadViewsFrom(realpath(__DIR__.'/views'), 'pragmarx/countries');
    }

    /**
     * Merge configuration.
     */
    protected function mergeConfig()
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

    protected function registerService()
    {
        $this->app->singleton('pragmarx.countries.cache', $cache = app(config('countries.cache.service')));

        $hydrator = new Hydrator();

        $this->app->singleton('pragmarx.countries', function () use ($cache, $hydrator) {
            $repository = new CountriesRepository($cache, new CurrenciesRepository(), $hydrator);

            $hydrator->setRepository($repository);

            return new Service($repository);
        });
    }
}
