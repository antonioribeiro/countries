<?php

namespace PragmaRX\Countries\Package\Services;

class Command
{
    public function line(string $line): void
    {
        echo "{$line}\n";
    }
}
