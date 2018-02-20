<?php

namespace PragmaRX\Countries\Update;

class Config
{
    protected $data = [
        'downloadable' => [
            'mledoze' => 'https://github.com/mledoze/countries/archive/master.zip',

            'rinvex' => 'https://github.com/rinvex/country/archive/master.zip',

            'natural_earth' => [
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_1_states_provinces.shx',

                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_populated_places.shx',

                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_countries.shx',

                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.cpg',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.dbf',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.prj',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.shp',
                'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/10m_cultural/ne_10m_admin_0_scale_rank_minor_islands.shx',
            ],

            'commerceguys' => 'https://github.com/commerceguys/tax/archive/master.zip',

            'timezonedb' => 'https://timezonedb.com/files/timezonedb.csv.zip',

            'world-currencies' => 'https://github.com/antonioribeiro/world-currencies/archive/master.zip',

            'country-nationality-list' => 'https://github.com/Dinu/country-nationality-list/archive/master.zip',
        ],

        'moveable' => [
            'third-party/mledoze/package/data' => 'third-party/mledoze/data',
            'third-party/mledoze/package/dist' => 'third-party/mledoze/dist',
            'third-party/rinvex/package/resources' => 'third-party/rinvex/data',
            'third-party/mledoze/package/data/*.svg' => 'flags',
            'third-party/mledoze/package/data/*.geo.json' => 'geo',
            'third-party/mledoze/package/data/*.topo.json' => 'topo',
            'third-party/commerceguys/package/resources/tax_type' => 'third-party/commerceguys/taxes/types',
            'third-party/commerceguys/package/resources/zone' => 'third-party/commerceguys/taxes/zones',
        ],

        'deletable' => [
            'third-party',
            'tmp',
        ],
    ];

    /**
     * @param $key
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function get($key)
    {
        return coollect($this->data[$key]);
    }
}
