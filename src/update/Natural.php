<?php

namespace PragmaRX\Countries\Update;

use PragmaRX\Countries\Package\Support\Base;

class Natural extends Base
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Updater
     */
    protected $updater;

    /**
     * @var States
     */
    protected $states;

    /**
     * Rinvex constructor.
     *
     * @param Helper $helper
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;
    }

    /**
     * @param $state
     * @return array
     */
    public function naturalToStateArray($state)
    {
        $state = [
            'name' => $state['name'],

            'alt_names' => explode('|', $state['name_alt']),

            'cca2' => $state['cca2'],

            'cca3' => $state['cca3'],

            'code_hasc' => $state['code_hasc'],

            'extra' => coollect($state)->sortByKey()->except([
                'name', 'name_alt', 'latitude', 'longitude', 'cca2', 'cca3',
                'iso_a2', 'iso_a3', 'type', 'type_en', 'postal',
                'iso_3166_2', 'code_hasc',
            ]),

            'geo' => [
                'latitude' => $state['latitude'],
                'longitude' => $state['longitude'],
            ],

            'iso_a2' => $state['iso_a2'],

            'iso_a3' => $state['iso_a3'],

            'iso_3166_2' => $state['iso_3166_2'],

            'postal' => $this->states->makeStatePostalCode($state),

            'type' => $state['type'],

            'type_en' => $state['type_en'],
        ];

        return $state;
    }

    /**
     * @param \PragmaRX\Coollection\Package\Coollection $country
     * @return mixed
     */
    public function fixNaturalOddCountries($country)
    {
        if ($country['iso_a2'] === '-99') {
            $country['iso_a2'] = $country['wb_a2'];

            $country['iso_a3'] = $country['wb_a3'];
        }

        return $country;
    }

    /**
     * Fill natural earth vector fields with mledoze data.
     *
     * @param $fields
     * @return mixed
     */
    public function fillNaturalFields($fields)
    {
        return $fields;
    }

    /**
     * @param States $states
     */
    public function setStates(States $states)
    {
        $this->states = $states;
    }
}
