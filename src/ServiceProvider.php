<?php

namespace PragmaRX\Countries;

use PragmaRX\Countries\Support\Hydrator;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Countries\Facade as Countries;
use PragmaRX\Countries\Support\CountriesRepository;
use PragmaRX\Countries\Support\CurrenciesRepository;
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
            __DIR__.'/config/config.php' => config_path('countries.php'),
        ], 'config');
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
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('countries.validation.enabled')) {
            $this->addValidations();
        }
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

    private function addValidations()
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
}
