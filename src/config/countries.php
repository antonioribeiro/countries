<?php

return [

    'cache' => [
        'enabled' => true,

        'service' => PragmaRX\Countries\Package\Support\Cache::class,

        'duration' => 180,
    ],

    'hydrate' => [
        'before' => true,

        'after' => true,

        'elements' => [
            'borders' => false,
            'cities' => false,
            'currencies' => false,
            'flag' => false,
            'geometry' => false,
            'states' => false,
            'taxes' => false,
            'timezones' => false,
            'timezones_times' => false,
            'topology' => false,
        ],
    ],

    'maps' => [
        'lca3' => 'cca3',
        'currencies' => 'currency',
    ],

    'validation' => [
        'enabled' => true,
        'rules' => [
            'country'           => 'name.common',
            'cca2',
            'cca2',
            'cca3',
            'ccn3',
            'cioc',
            'currencies'        => 'ISO4217',
            'language_short'    => 'ISO639_3',
        ],
    ],
];
