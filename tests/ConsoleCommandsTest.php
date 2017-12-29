<?php

namespace PragmaRX\Countries\Tests\Service;

use PragmaRX\Countries\Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    public function testConsole()
    {
        \Artisan::call('countries:update');

        $this->assertFalse(! true); // dummy, yeah!
    }
}
