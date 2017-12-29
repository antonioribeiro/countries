<?php

namespace PragmaRX\Countries\Tests\Service;

use PragmaRX\Countries\Tests\TestCase;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\Collection;
use PragmaRX\Countries\Package\Facade as Countries;

class CountriesTest extends TestCase
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
        $shortName = Countries::whereLanguage('Portuguese')->count();
        $this->assertGreaterThan(0, $shortName);
        $this->assertEquals($shortName, Countries::where('languages.por', 'Portuguese')->count());
    }

    public function testWhereCurrency()
    {
        $shortName = Countries::where('ISO4217', 'EUR')->count();
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

    public function testMapping()
    {
        $this->assertGreaterThan(0, Countries::where('lca3', 'BRA')->count());
    }

    public function testCurrencies()
    {
        $this->assertEquals(Countries::currencies()->count(), 289);
    }

    public function testTimezone()
    {
        $this->assertEquals(
            Countries::where('cca3', 'FRA')->first()->hydrate('timezone')->timezone,
            'Europe/Paris'
        );

        $this->assertEquals(
            Countries::where('name.common', 'United States')->first()->timezone->NC,
            'America/New_York'
        );
    }

    public function testHydratorMethods()
    {
        $this->assertEquals(
            Countries::where('cca3', 'FRA')->first()->hydrate('timezone')->timezone,
            'Europe/Paris'
        );

        $this->assertEquals(
            Countries::where('cca3', 'JPN')->first()->hydrateTimezone()->timezone,
            'Asia/Tokyo'
        );

        $this->assertInstanceOf(
            Collection::class,
            Countries::where('cca3', 'BRA')->first()->hydrate('timezone')
        );

        $this->assertInstanceOf(
            Collection::class,
            Countries::where('cca3', 'ITA')->first()->hydrate('timezone')->states
        );

        $this->assertInstanceOf(
            Collection::class,
            Countries::where('cca3', 'ITA')->first()->hydrateTimezone()->states
        );
    }

    public function testOldIncorrectStates()
    {
        $c = Countries::where('cca3', 'BRA')->first()->hydrate('states');

        $this->assertEquals('BRA-595', $c->states->RO->adm1_code);
        $this->assertEquals('BR.RO', $c->states->RO->code_hasc);
        $this->assertEquals('RO', $c->states->RO->postal);

        $this->assertEquals(
            'Puglia',
            Countries::where('cca3', 'ITA')->first()->hydrate('timezone')->states['BA']['region']
        );

        $this->assertEquals(
            'Sicilia',
            Countries::where('cca3', 'ITA')->first()->hydrate('timezone')->states['TP']['region']
        );
    }
}
