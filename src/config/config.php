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
            'borders' => false,
            'topology' => true,
            'geometry' => true,
            'collection' => true,
        ],
    ],

];
