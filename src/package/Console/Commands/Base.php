<?php

namespace PragmaRX\Countries\Package\Console\Commands;

use Illuminate\Console\Command;

class Base extends Command
{
    /**
     * Draw a line in console.
     *
     * @param int $len
     */
    public function drawLine($len = 80)
    {
        if (is_string($len)) {
            $len = strlen($len);
        }

        $this->line(str_repeat('-', max($len, 80)));
    }
}
