<?php

namespace PragmaRX\Countries\Tests\PhpUnit\Service;

use PragmaRX\Countries\Facade as Countries;
use PragmaRX\Countries\Support\Collection;
use PragmaRX\Countries\Support\CountriesCollection;
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
        $this->assertInstanceOf(CountriesCollection::class, Countries::collection([]));
    }

    public function test_can_hydrate_all_countries_borders()
    {
        Countries::all()->each(function($country) {
            $hydrated = Countries::getRepository()
                            ->hydrate(
                                Countries::getRepository()
                                    ->collection([$country]), ['borders' => true]
                            );

            if (count($hydrated->first()->borders)) {
                $this->assertNotEmpty(($first = $hydrated->first()->borders->first()->first())->name);

                $this->assertInstanceOf(Collection::class, $first);
            } else {
                $this->assertNull($hydrated->first()->borders->first());
            }
        });
    }

    public function test_can_get_a_single_border()
    {
        $name = Countries::getRepository()->hydrate(
            Countries::where('name.common', 'Brazil'),
            ['borders' => true]
        )->first()->borders->reverse()->first()->first()->name->common;

        $this->assertEquals('Venezuela', $name);
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
}
