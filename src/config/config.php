<?php

return [

    'cache' => [
        'enabled' => true,

        'service' => PragmaRX\Countries\Support\Cache::class,

        'duration' => 180,
    ],

    'hydrate' => [
        'before' => true,

        'after' => true,

        'elements' => [
            'flag' => true,
            'currency' => true,
            'states' => true,
            'timezone' => true,
            'borders' => false,
            'topology' => true,
            'geometry' => true,
            'collection' => true,
        ],
    ],
    'maps' => [
        'lca3' => 'ISO639_3',
        'currency' => 'ISO4217',
    ],

    'validation' => [
        'enabled'    => true,
        'rules'    => [
            'country'            => 'name.common',
            'cca2',
            'cca2',
            'cca3',
            'ccn3',
            'cioc',
            'currency'            => 'ISO4217',
            'language',
            'language_short'    => 'ISO639_3',
        ],
    ],

];
