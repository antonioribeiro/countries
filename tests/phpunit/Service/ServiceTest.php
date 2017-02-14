<?php

namespace PragmaRX\Countries\Tests\PhpUnit\Service;

use PragmaRX\Countries\Facade as Countries;
use PragmaRX\Countries\Tests\PhpUnit\TestCase;

class ServiceTest extends TestCase
{
    public function test_countries_is_instantiable()
    {
        $brazil = Countries::where('name.common', 'Brazil')->first();

        $this->assertEquals($brazil->name->common, 'Brazil');
    }

    public function test_can_hydrate_all_countries_borders()
    {
        $repository = Countries::getRepository();

        Countries::all()->each(function($country) use ($repository) {
            $hydrated = $repository->hydrate($repository->collection([$country]), ['borders' => true]);

            if (count($hydrated->first()->borders)) {
                $this->assertNotEmpty($hydrated->first()->borders->first()->first()->name);
            } else {
                $this->assertNull($hydrated->first()->borders->first());
            }
        });
    }

    public function test_can_get_a_single_border()
    {
        $name = Countries::getRepository()->hydrate(
            Countries::where('name.common', 'Brazil'), ['borders' => true]
        )->first()->borders->reverse()->first()->first()->name->common;

        $this->assertEquals('Venezuela', $name);
    }
}
