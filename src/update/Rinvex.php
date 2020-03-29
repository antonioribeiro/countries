<?php

namespace PragmaRX\Countries\Update;

use Exception;
use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\Base;

class Rinvex extends Base
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
    protected $natural;

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
     * Fill array with Rinvex usable data.
     *
     * @param \PragmaRX\Coollection\Package\Coollection $natural
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function fillRinvexFields($natural)
    {
        $mergeable = [
            'calling_code' => 'dialling',
            //            'borders'      => 'geo',
            'area'         => 'geo',
            'continent'    => 'geo',
            'landlocked'   => 'geo',
            'region'       => 'geo',
            'region_un'    => 'geo',
            'region_wb'    => 'geo',
            'subregion'    => 'geo',
            'latlng'       => 'geo',
        ];

        coollect($mergeable)->each(function ($to, $key) use (&$natural) {
            if (isset($natural[$key])) {
                $natural->overwrite([$to => [$key => $natural[$key]]]);

                unset($natural[$key]);
            }
        });

        return $natural;
    }

    /**
     * @param $result
     * @param string $type
     * @return null|Coollection
     */
    public function findRinvex($result, $type)
    {
        return $this->helper->loadJson(strtolower($result['cca2']), "third-party/rinvex/data/$type");
    }

    /**
     * Find the Rinvex country.
     *
     * @param $item
     * @return null|\PragmaRX\Coollection\Package\Coollection
     */
    public function findRinvexCountry($item)
    {
        return $this->findRinvex($item, 'data');
    }

    /**
     * Find the Rinvex state.
     *
     * @param $item
     * @return null|\PragmaRX\Coollection\Package\Coollection
     */
    public function findRinvexStates($item)
    {
        return $this->findRinvex($item, 'divisions');
    }

    /**
     * Find the Rinvex state.
     *
     * @param $country
     * @param \PragmaRX\Coollection\Package\Coollection $needle
     * @return null|Coollection
     */
    public function findRinvexState($country, $needle)
    {
        $states = $this->findRinvex($country, 'divisions')->map(function ($state, $postal) {
            $state['postal'] = $postal;

            $state['name'] = $this->helper->fixUtf8($state['name']);

            return $state;
        });

        if ($states->isEmpty()) {
            return $states;
        }

        $state = $states->filter(function ($rinvexState) use ($needle) {
            return $rinvexState->postal == $needle->postal ||
                $rinvexState->name == $needle['name'] ||
                utf8_encode($rinvexState->name) == $needle['name'] ||
                $rinvexState->alt_names->contains($needle['name']) ||
                $rinvexState->alt_names->contains(function ($name) use ($needle) {
                    return $needle->alt_names->contains($name);
                });
        })->first();

        if (is_null($state)) {
            return coollect();
        }

        return coollect($state);
    }

    /**
     * Find the Rinvex translation.
     *
     * @param $result
     * @return null|\PragmaRX\Coollection\Package\Coollection
     */
    public function findRinvexTranslations($result)
    {
        return $this->helper->loadJson(strtolower($result['cca2']), 'third-party/rinvex/data/translations');
    }

    /**
     * Merge country data with Rinvex data.
     *
     * @param Coollection $natural
     * @param Coollection $rinvex
     * @param $translation
     * @param string $suffix
     * @return mixed|\PragmaRX\Coollection\Package\Coollection
     */
    public function mergeWithRinvex($natural, $rinvex, $translation, $suffix = '_rinvex')
    {
        $defaultToRinvex = coollect([
            'currency',
            'languages',
            'dialling',
        ]);

        $merge = coollect([
            'geo',
            'translations',
            'flag',
        ]);

        $natural = $this->fillRinvexFields($natural);

        if ($rinvex->isEmpty()) {
            return $natural;
        }

        $rinvex['translations'] = $translation;

        $rinvex['flag'] = ['emoji' => $rinvex['extra']['emoji']];

        $result = [];

        foreach ($rinvex->keys()->merge($natural->keys()) as $key) {
            $naturalValue = arrayable($var = $natural->get($key)) ? $var->sortByKeysRecursive()->toArray() : $var;

            $rinvexValue = arrayable($var = $rinvex->get($key)) ? $var->sortByKeysRecursive()->toArray() : $var;

            if (is_null($naturalValue) || is_null($rinvexValue)) {
                $result[$key] = $rinvexValue ?: $naturalValue;

                continue;
            }

            if ($rinvexValue !== $naturalValue && $merge->contains($key)) {
                $result[$key] = coollect($naturalValue)->overwrite($rinvexValue);

                continue;
            }

            if ($rinvexValue !== $naturalValue && ! $defaultToRinvex->contains($key)) {
                $result[$key.$suffix] = $rinvexValue; // Natural Earth Vector
            }

            $result[$key] = $defaultToRinvex->contains($key)
                ? $rinvexValue
                : $naturalValue; // Natural Earth Vector
        }

        return coollect($result)->sortBy(function ($value, $key) {
            return $key;
        });
    }

    /**
     * Merge state data with rinvex divisions data.
     *
     * @param $states
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function mergeCountryStatesWithRinvex($states)
    {
        return coollect($states)->map(function ($state) {
            return $this->mergeStateWithRinvex($state);
        });
    }

    /**
     * @param $state
     * @return \PragmaRX\Coollection\Package\Coollection
     * @throws Exception
     */
    public function mergeStateWithRinvex($state)
    {
        $country = $this->updater->getCountries()->where('cca3', $iso_a3 = $state['iso_a3'])->first();

        if (is_null($country)) {
            dump($state);

            throw new Exception('Country not found for state');
        }

        $state = coollect($this->natural->naturalToStateArray($state));

        $rinvex = $this->findRinvexState($country, $state);

        if ($rinvex->isEmpty()) {
            return $state;
        }

        $rinvex = $this->rinvexToStateArray($rinvex, $state['cca3'], $state->postal, $country);

        return $state->overwrite($rinvex);
    }

    /**
     * @param \PragmaRX\Coollection\Package\Coollection $rinvex
     * @param $cca3
     * @param $postal
     * @param $country
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function rinvexToStateArray($rinvex, $cca3, $postal, $country)
    {
        $mergeable = [
            'cca2' => $country['cca2'],

            'cca3' => $cca3,

            'iso_a2' => $country['iso_a2'],

            'iso_a3' => $country['iso_a3'],

            'iso_3166_2' => "{$country['cca2']}-$postal",

            'postal' => $postal,
        ];

        return $rinvex->overwrite($mergeable);
    }
}
