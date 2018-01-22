<?php

use PragmaRX\Countries\Package\Countries;
use PragmaRX\Countries\Package\Services\Config;

require __DIR__.'/vendor/autoload.php';

$countries = new Countries(new Config([
    'hydrate' => [
        'elements' => [
            'currencies' => true,
            'flag' => true,
            'timezones' => true,
        ],
    ],
]));

$countries->getCache()->clear();

dd(
    $countries->where('cca2', 'IT')->first()->currencies->EUR->coins->frequent->first()
);
