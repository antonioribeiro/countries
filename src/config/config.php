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
            'topology' => false,
            'geometry' => true,
            'collection' => true,
        ],
    ],

];
