<?php

namespace PragmaRX\Countries\Tests\PhpUnit\Service;

use PragmaRX\Countries\Support\Collection;
use PragmaRX\Countries\Facade as Countries;
use PragmaRX\Countries\Tests\PhpUnit\TestCase;

class ServiceTest extends TestCase
{
    public function test_countries_is_instantiable()
    {
        $brazil = Countries::where('name.common', 'Brazil')->first();

        $this->assertEquals($brazil->name->common, 'Brazil');
    }

    public function test_can_make_a_collection()
    {
        $this->assertInstanceOf(Collection::class, Countries::collection([]));
    }

    public function test_can_hydrate_all_countries_borders()
    {
        Countries::all()->hydrate('borders')->each(function ($hydrated) {
            if ($hydrated->borders->count()) {
                $this->assertNotEmpty(($first = $hydrated->borders->first())->name);

                $this->assertInstanceOf(Collection::class, $first);
            } else {
                $this->assertNull($hydrated->borders->first());
            }
        });
    }

    public function test_can_get_a_single_border()
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

    public function test_states_are_hydrated()
    {
        $this->assertEquals(Countries::where('name.common', 'Brazil')->first()->states->count(), 27);

        $this->assertEquals(Countries::where('cca3', 'USA')->first()->states->count(), 51);
    }

    public function test_can_get_a_state()
    {
        $this->assertEquals(
            'Goiás',
            Countries::where('name.common', 'Brazil')->first()->states->first()->name
        );
    }

    public function test_all_hydrations()
    {
        $elements = array_keys(config('countries.hydrate.elements'));

        $hydrated = Countries::where('tld.0', '.nz')->hydrate($elements);

        $this->assertNotNull($hydrated->first()->geometry);
//        $this->assertNotNull($hydrated->first()->topology);
        $this->assertNotNull($hydrated->first()->states);
        $this->assertNotNull($hydrated->first()->borders);
        $this->assertNotNull($hydrated->first()->flag->sprite);
    }
}
