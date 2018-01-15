<?php

namespace PragmaRX\Countries\Tests\Service;

use PragmaRX\Countries\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ConsoleCommandsTest extends TestCase
{
    public function testConsole()
    {
        $this->assertEquals(0, $this->artisan('countries:update')); // dummy, yeah!
    }
}
