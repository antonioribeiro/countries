<?php

namespace PragmaRX\Countries\Package;

use Illuminate\Support\Facades\Validator;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\Hydrator;
use PragmaRX\Countries\Package\Facade as Countries;
use PragmaRX\Countries\Package\Console\Commands\Update;
use PragmaRX\Countries\Package\Support\CountriesRepository;
use PragmaRX\Countries\Package\Support\CurrenciesRepository;
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
    protected function configurePaths()
    {
        $this->publishes([
            __COUNTRIES_DIR__._dir('/src/config/countries.php') => config_path('countries.php'),
        ], 'config');
    }

    /**
     * Create the collection hydrator macro.
     */
    private function createCollectionHydrator()
    {
        Coollection::macro('hydrate', function ($elements) {
            return Countries::hydrate($this, $elements);
        });
    }

    protected function definePath()
    {
        if (! defined('__COUNTRIES_DIR__')) {
            define(
                '__COUNTRIES_DIR__',
                realpath(
                    __DIR__._dir('/../../')
                )
            );
        }
    }

    /**
     * Merge configuration.
     */
    protected function mergeConfig()
    {
        $this->mergeConfigFrom(
            __COUNTRIES_DIR__._dir('/src/config/countries.php'), 'countries'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('countries.validation.enabled')) {
            $this->addValidators();
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->definePath();

        $this->configurePaths();

        $this->mergeConfig();

        $this->registerService();

        $this->registerUpdateCommand();

        $this->createCollectionHydrator();
    }

    /**
     * Register the service.
     */
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

    /**
     * Add validators.
     */
    private function addValidators()
    {
        foreach (config('countries.validation.rules') as $ruleName => $countryAttribute) {
            if (is_int($ruleName)) {
                $ruleName = $countryAttribute;
            }

            Validator::extend($ruleName, function ($attribute, $value) use ($countryAttribute) {
                return ! Countries::where($countryAttribute, $value)->isEmpty();
            }, 'The :attribute must be a valid '.$ruleName.'.');
        }
    }

    /**
     * Register update command.
     */
    private function registerUpdateCommand()
    {
        $this->app->singleton($command = 'countries.update.command', function () {
            return new Update();
        });

        $this->commands($command);
    }
}
