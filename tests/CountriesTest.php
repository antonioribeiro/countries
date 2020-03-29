<?php

namespace PragmaRX\Countries\Tests\Service;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Countries;
use PragmaRX\Countries\Update\Config as ServiceConfig;
use PragmaRX\Countries\Update\Helper;
use PragmaRX\Countries\Update\Updater;

class CountriesTest extends PHPUnitTestCase
{
    public function setUp(): void
    {
        ini_set('memory_limit', '2048M');

        Countries::getCache()->clear();
    }

    public function testUpdateCountries()
    {
        if (! function_exists('xdebug_get_code_coverage')) {
            ini_set('memory_limit', '4096M');

            $config = new ServiceConfig();

            $helper = new Helper($config);

            $updater = new Updater($config, $helper);

            $updater->update();
        }

        $this->assertTrue(! false);
    }

    public function testCountriesCanFilterOneCountry()
    {
        $brazil = Countries::where('name.common', 'Brazil')->first();

        $this->assertEquals($brazil->name->common, 'Brazil');
    }

    public function testCountriesHydrateCountryBordersFromOneCountry()
    {
        $brazil = Countries::where('name.common', 'Brazil')->first()->hydrateBorders();

        $this->assertEquals(740, $brazil->borders->where('name.common', 'Suriname')->first()->ccn3);
    }

    public function testCanHydrateAllCountriesBorders()
    {
        Countries::all()->each(function ($country) {
            $hydrated = $country->hydrate('borders');

            if ($hydrated->borders->count()) {
                $this->assertNotEmpty(($first = $hydrated->borders->first())->name);

                $this->assertInstanceOf(Coollection::class, $first);
            } else {
                $this->assertTrue($hydrated->borders->first()->count() === 0);
            }
        });
    }

    public function testCanHydrateAllTimezones()
    {
        Countries::all()->each(function ($country) {
            $hydrated = $country->hydrate('timezones');

            if ($hydrated->timezones->count()) {
                $this->assertNotEmpty(($first = $hydrated->timezones->first())->abbreviations);

                $this->assertInstanceOf(Coollection::class, $first);
            } else {
                $this->assertEquals(0, $hydrated->timezones->first()->count());
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
        $this->assertEquals(27, Countries::where('name.common', 'Brazil')->first()->hydrate('states')->states->count());

        $this->assertEquals(51, Countries::where('cca3', 'USA')->first()->hydrate('states')->states->count());

        $this->assertEquals(
            'Northeast',
            Countries::where('cca3', 'USA')->first()->hydrate('states')->states->NY->extra->region
        );
    }

    public function testCanGetAState()
    {
        $this->assertEquals(
            'Agrigento',
            Countries::where('name.common', 'Italy')->first()->hydrate('states')->states->sortBy(function ($state) {
                return $state['name'];
            })->first()->name
        );
    }

    public function testAllHydrations()
    {
        $elements = Countries::getConfig()->get('hydrate.elements')->map(function ($value) {
            return true;
        })->toArray();

        $hydrated = Countries::where('tld.0', '.nz')->hydrate($elements);

        $this->assertNotNull($hydrated->first()->borders);
        $this->assertNotNull($hydrated->first()->cities);
        $this->assertNotNull($hydrated->first()->currencies);
        $this->assertNotNull($hydrated->first()->flag->sprite);
        $this->assertNotNull($hydrated->first()->geometry);
        $this->assertNotNull($hydrated->first()->hydrateStates()->states);
        $this->assertNotNull($hydrated->first()->taxes);
        $this->assertNotNull($hydrated->first()->timezones);
        $this->assertNotNull($hydrated->first()->topology);
    }

    public function testWhereLanguage()
    {
        $shortName = Countries::whereLanguage('Papiamento')->count();

        $this->assertGreaterThan(0, $shortName);

        $this->assertEquals($shortName, Countries::where('languages.pap', 'Papiamento')->count());
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
        $this->assertGreaterThan(0, Countries::where('cca3', 'BRA')->count());
    }

    public function testCurrencies()
    {
        $this->assertEquals(Countries::currencies()->count(), 153);

        $this->assertEquals(
            'CHF1000',
            Countries::where('cca3', 'CHE')->first()->hydrate('currencies')->currencies->chf->banknotes->frequent->last()
        );
    }

    public function testTimezones()
    {
        $this->assertEquals(
            Countries::where('cca3', 'FRA')->first()->hydrate('timezones')->timezones->first()->zone_name,
            'Europe/Paris'
        );

        $this->assertEquals(
            Countries::where('name.common', 'United States')->first()->hydrate('timezones')->timezones->first()->zone_name,
            'America/Adak'
        );
    }

    public function testHydratorMethods()
    {
        $this->assertEquals(
            Countries::where('cca3', 'FRA')->first()->hydrate('timezones')->timezones->europe_paris->zone_name,
            'Europe/Paris'
        );

        $this->assertEquals(
            Countries::where('cca3', 'JPN')->first()->hydrateTimezones()->timezones->asia_tokyo->zone_name,
            'Asia/Tokyo'
        );
    }

    public function testOldIncorrectStates()
    {
        $c = Countries::where('cca3', 'BRA')->first()->hydrate('states');

        $this->assertEquals('BR-RO', $c->states->RO->iso_3166_2);
        $this->assertEquals('BR.RO', $c->states->RO->code_hasc);
        $this->assertEquals('RO', $c->states->RO->postal);

        $this->assertEquals(
            'Puglia',
            Countries::where('cca3', 'ITA')->first()->hydrate('states')->states['BA']['region']
        );

        $this->assertEquals(
            'Sicilia',
            Countries::where('cca3', 'ITA')->first()->hydrate('states')->states['TP']['region']
        );
    }

    public function testCanGetCurrency()
    {
        $this->assertEquals(
            'R$',
            Countries::where('name.common', 'Brazil')->first()->hydrate('currencies')->currencies->BRL->units->major->symbol
        );
    }

    public function testTranslation()
    {
        $this->assertEquals(
            'Repubblica federativa del Brasile',
            Countries::where('name.common', 'Brazil')->first()->translations->ita->official
        );
    }

    public function testCitiesHydration()
    {
        $this->assertEquals(
            Countries::where('cca3', 'FRA')->first()->hydrate('cities')->cities->paris->timezone,
            'Europe/Paris'
        );
    }

    public function testNumberOfCurrencies()
    {
        $number = Countries::all()->hydrate('currencies')->pluck('currencies')->map(function ($value) {
            return $value->keys()->flatten()->toArray();
        })->flatten()->filter(function ($value) {
            return $value !== 'unknown';
        })->sort()->values()->unique()->count();

        return $this->assertEquals(161, $number);
    }

    public function testNumberOfBorders()
    {
        $number = Countries::all()->pluck('borders')->map(function ($value) {
            if (is_null($value)) {
                return [];
            }

            return $value->keys()->flatten()->toArray();
        })->count();

        $this->assertEquals(267, $number);
    }

    public function testNumberOfLanguages()
    {
        $number = Countries::all()->pluck('languages')->map(function ($value) {
            if (is_null($value)) {
                return;
            }

            return $value->keys()->flatten()->mapWithKeys(function ($value, $key) {
                return [$value => $value];
            })->toArray();
        })->flatten()->unique()->values()->reject(function ($value) {
            return is_null($value);
        })->count();

        $this->assertEquals(142, $number);
    }

    public function testFindCountryByCca2()
    {
        $this->assertEquals(
            'Puglia',
            Countries::where('cca2', 'IT')->first()->hydrate('states')->states['BA']['region']
        );
    }

    public function testStatesFromNetherlands()
    {
        $neds = Countries::where('name.common', 'Netherlands')
            ->first()
            ->hydrate('states')
            ->states
            ->sortBy('name')
            ->pluck('name')
            ->count();

        $this->assertEquals(15, $neds);
    }

    public function testHydrateOneElementOnly()
    {
        $this->assertEquals(
            110,
            Countries::where('cca2', 'IT')->first()->hydrate('states')->states->count()
        );
    }

    public function testHydrateEurope()
    {
        $this->assertEquals(
            'Europe Union',
            Countries::where('cca3', 'EUR')->first()->name->common
        );
    }

    public function testLoadAllCurrencies()
    {
        $this->assertEquals(
            '€1',
            Countries::where('cca2', 'IT')->first()->hydrate('currencies')->currencies->EUR->coins->frequent->first()
        );
    }

    public function testCanGetPropertyWithAnyCase()
    {
        $c = Countries::where('cca2', 'IT')->first()->hydrate('currencies')->currencies;

        $this->assertEquals(
            '€1',
            $c->EUR->coins->frequent->first()
        );

        $this->assertEquals(
            '50c',
            $c->eur->coins->frequent->last()
        );
    }

    public function testHydrateTaxes()
    {
        $this->assertEquals(
            'it_vat',
            Countries::where('cca2', 'IT')->first()->hydrate('taxes')->taxes->vat->zone
        );
    }

    public function stringForComparison($string)
    {
        return str_replace(
            ["\n", '\n', '\\', '/', ' '],
            ['',   '',   '',   '',  ''],
            $string
        );
    }

    public function testCanSortArrayByKeys()
    {
        $a = [
            'f' => 'e',
            'd' => [
                'c' => [
                    'b' => 1,
                ],
                'a' => 2,
            ],
        ];

        $b = [
            'd' => [
                'a' => 2,
                'c' => [
                    'b' => 1,
                ],
            ],
            'f' => 'e',
        ];

        array_sort_by_keys_recursive($a);

        $this->assertEquals($b, $a);
    }

    public function testCanHydrateTimezonesTimes()
    {
        $this->assertEquals(
            Countries::where('name.common', 'United States Virgin Islands')->first()->hydrate('timezones_times')->timezones->first()->times->time_start,
            '-1825098837'
        );
    }

    public function testPluck()
    {
        $c = new Countries;

        $this->assertEquals(267, $c->all()->count());

        $this->assertEquals(267, $c->all()->pluck('name.common')->count());
    }
}
