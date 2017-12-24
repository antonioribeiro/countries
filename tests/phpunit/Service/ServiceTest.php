<?php

namespace PragmaRX\Countries\Tests\PhpUnit\Service;

use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Tests\PhpUnit\TestCase;
use PragmaRX\Countries\Package\Support\Collection;
use PragmaRX\Countries\Package\Facade as Countries;

class ServiceTest extends TestCase
{
    public function testCountriesIsInstantiable()
    {
        $brazil = Countries::where('name.common', 'Brazil')->first();

        $this->assertEquals($brazil->name->common, 'Brazil');
    }

    public function testCanMakeACollection()
    {
        $this->assertInstanceOf(Coollection::class, Countries::collection([]));
    }

    public function testCanHydrateAllCountriesBorders()
    {
        Countries::all()->take(5)->hydrate('borders')->each(function ($hydrated) {
            if ($hydrated->borders->count()) {
                $this->assertNotEmpty(($first = $hydrated->borders->first())->name);

                $this->assertInstanceOf(Coollection::class, $first);
            } else {
                $this->assertNull($hydrated->borders->first());
            }
        });
    }

    public function testCanGetASingleBorder()
    {
        $this->assertEquals(
            'Venezuela',
            Countries::where('name.common', 'Brazil')
                ->hydrate('borders')
                ->first()
                ->borders
                ->reverse()
                ->first()
                ->name
                ->common
        );
    }

    public function testCountryDoesNotExist()
    {
        $this->assertTrue(
            Countries::where('name.common', 'not a country')->isEmpty()
        );
    }

    public function testStatesAreHydrated()
    {
        $this->assertEquals(Countries::where('name.common', 'Brazil')->first()->states->count(), 27);

        $this->assertEquals(Countries::where('cca3', 'USA')->first()->states->count(), 51);
    }

    public function testCanGetAState()
    {
        $this->assertEquals(
            'Santa Cruz',
            Countries::where('name.common', 'Argentina')->first()->states->first()->name
        );
    }

    public function testAllHydrations()
    {
        $elements = array_keys(config('countries.hydrate.elements'));

        $hydrated = Countries::where('tld.0', '.nz')->hydrate($elements);

        $this->assertNotNull($hydrated->first()->geometry);
        $this->assertNotNull($hydrated->first()->states);
        $this->assertNotNull($hydrated->first()->borders);
        $this->assertNotNull($hydrated->first()->flag->sprite);
    }


    public function testWhereLanguage()
    {
        $shortName = Countries::where('ISO639_3', 'por')->count();
        $this->assertGreaterThan(0, $shortName);
        $this->assertEquals($shortName, Countries::where('language', 'Portuguese')->count());
    }

    public function testWhereCurrency()
    {
        $shortName = Countries::where('ISO4217', 'EUR')->count();
        $this->assertGreaterThan(0, $shortName);
    }

    public function testMapping()
    {
        $shortName = Countries::where('lca3', 'por')->count();

        $this->assertGreaterThan(0, $shortName);
    }

    public function testMagicCall()
    {
        $this->assertEquals(
            Countries::whereNameCommon('Brazil')->count(),
            Countries::where('name.common', 'Brazil')->count()
        );

        $this->assertEquals(
            Countries::whereISO639_3('por')->count(),
            Countries::where('ISO639_3', 'por')->count()
        );

        $this->assertEquals(
            Countries::whereLca3('por')->count(),
            Countries::where('lca3', 'por')->count()
        );
    }
}
