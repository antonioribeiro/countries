<?php

namespace PragmaRX\Countries\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\Collection;

class Nationality extends Base
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
     * Rinvex constructor.
     *
     * @param Helper  $helper
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;
    }

    /**
     * @return Collection
     */
    public function load()
    {
        $mledoze = countriesCollect($this->helper->loadJson('countries', 'third-party/mledoze/dist'))->mapWithKeys(
            function ($country) {
                $country = $this->updater->addDataSource($country, 'mledoze');

                $country = $this->updater->addRecordType($country, 'country');

                return [$country['cca3'] => $country];
            },
        );

        return $mledoze;
    }

    /**
     * Fill mledoze fields with natural earth vector data.
     *
     * @param $fields
     *
     * @return mixed
     */
    public function fillMledozeFields($fields)
    {
        $fields['name_nev'] = $fields['name'];

        $fields['name'] = [
            'common' => $fields['name'],
            'official' => $fields['formal_en'],
        ];

        $fields['cca2'] = $fields['iso_a2'] == '-99' ? $fields['adm0_a3'] : $fields['iso_a2'];
        $fields['ccn3'] = $fields['iso_n3'] == '-99' ? $fields['adm0_a3'] : $fields['iso_a2'];
        $fields['cca3'] = $fields['iso_a3'] == '-99' ? $fields['adm0_a3'] : $fields['iso_a2'];

        $fields['region'] = $fields['region_un'];

        $fields['borders'] = [];

        $fields['curencies'] = [];

        $fields['notes'] = ['Incomplete record due to missing mledoze country.'];

        return $fields;
    }

    /**
     * Find a mledoze country from natural earth vector data.
     *
     * @param Collection $mledoze
     * @param Collection $natural
     *
     * @return array
     */
    public function findMledozeCountry($mledoze, $natural)
    {
        [$country, $countryCode] = $this->updater->findCountryByAnyField($mledoze, $natural);

        if (!$country->isEmpty()) {
            return [countriesCollect($this->helper->arrayKeysSnakeRecursive($country)), $countryCode];
        }

        return [countriesCollect(), $countryCode];
    }

    /**
     * Merge the two countries sources.
     *
     * @param \PragmaRX\Countries\Package\Support\Collection $mledoze
     * @param \PragmaRX\Countries\Package\Support\Collection $natural
     * @param string                                         $suffix
     *
     * @return mixed
     */
    public function mergeWithMledoze($mledoze, $natural, $suffix = '_nev')
    {
        if ($mledoze->isEmpty() || $natural->isEmpty()) {
            return $mledoze->isEmpty()
                ? $this->fillMledozeFields($natural)
                : $this->natural->fillNaturalFields($mledoze);
        }

        $result = [];

        foreach ($mledoze->keys()->merge($natural->keys()) as $key) {
            $naturalValue = $natural->get($key);
            $mledozeValue = $mledoze->get($key);

            if (is_null($naturalValue) || is_null($mledozeValue)) {
                $result[$key] = $mledozeValue ?: $naturalValue;

                continue;
            }

            if ($key == 'data_sources') {
                $result[$key] = $mledozeValue->merge($naturalValue);

                continue;
            }

            if ($mledozeValue !== $naturalValue) {
                $result[$key . $suffix] = $naturalValue; // Natural Earth Vector
            }

            $result[$key] = $mledozeValue; // Natural Earth Vector
        }

        return countriesCollect($result)->sortBy(function ($value, $key) {
            return $key;
        });
    }
}
