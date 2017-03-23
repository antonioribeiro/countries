<?php

namespace PragmaRX\Countries\Tests\PhpUnit;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Countries\ServiceProvider as CountriesServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            CountriesServiceProvider::class,
        ];
    }
}
