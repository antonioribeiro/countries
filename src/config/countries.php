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
        'enabled' => true,
        'rules' => [
            'country'           => 'name.common',
            'cca2',
            'cca2',
            'cca3',
            'ccn3',
            'cioc',
            'currency'          => 'ISO4217',
            'language_short'    => 'ISO639_3',
        ],
    ],

    'data' => [
        'downloadable' => [
            'mledoze' => 'https://raw.githubusercontent.com/mledoze/countries/master/dist/countries.json',

            'natural_earth' => [
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.shx',
            ]
        ],
    ],
];
