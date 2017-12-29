<?php

namespace PragmaRX\Countries\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Countries\Package\ServiceProvider as CountriesServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            CountriesServiceProvider::class,
        ];
    }
}
