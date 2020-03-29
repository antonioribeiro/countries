<?php

namespace PragmaRX\Countries\Update;

use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\Base;

class Mledoze extends Base
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
     * @var Natural
     */
    private $natural;

    /**
     * Rinvex constructor.
     *
     * @param Helper $helper
     * @param Natural $natural
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Natural $natural, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;

        $this->natural = $natural;
    }

    /**
     * @return Coollection
     */
    public function loadMledozeCountries()
    {
        $mledoze = coollect($this->helper->loadJson('countries', 'third-party/mledoze/dist'))->mapWithKeys(function (
            $country
        ) {
            $country = $this->updater->addDataSource($country, 'mledoze');

            $country = $this->updater->addRecordType($country, 'country');

            return [$country['cca3'] => $country];
        });

        return $mledoze;
    }

    /**
     * Fill mledoze fields with natural earth vector data.
     *
     * @param $fields
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
     * @param Coollection $mledoze
     * @param Coollection $natural
     * @return array
     */
    public function findMledozeCountry($mledoze, $natural)
    {
        [$country, $countryCode] = $this->updater->findCountryByAnyField($mledoze, $natural);

        if (! $country->isEmpty()) {
            return [coollect($this->helper->arrayKeysSnakeRecursive($country)), $countryCode];
        }

        return [coollect(), $countryCode];
    }

    private function makeFlag($result)
    {
        if (isset($result['flag']) && is_string($result['flag'])) {
            $result['flag'] = ['emoji' => $result['flag']];
        }

        return $result;
    }

    /**
     * Merge the two countries sources.
     *
     * @param \PragmaRX\Coollection\Package\Coollection $mledoze
     * @param \PragmaRX\Coollection\Package\Coollection $natural
     * @param string $suffix
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
                $result[$key.$suffix] = $naturalValue; // Natural Earth Vector
            }

            $result[$key] = $mledozeValue; // Natural Earth Vector
        }

        $result = $this->makeFlag($result);

        return coollect($result)->sortBy(function ($value, $key) {
            return $key;
        });
    }
}
