<?php

namespace PragmaRX\Countries\Package\Support;

use Illuminate\Support\Str;
use PragmaRX\Coollection\Package\Coollection;

class Hydrator
{
    /**
     * Countries repository.
     *
     * @var
     */
    protected $repository;

    /**
     * Can hydrate?
     *
     * @param $element
     * @param $enabled
     * @param $countryCode
     * @return bool
     */
    protected function canHydrate($element, $enabled, $countryCode)
    {
        return ($enabled || config('countries.hydrate.elements.'.$element)) &&
                ! isset($this->repository->countries[$countryCode]['hydrated'][$element]);
    }

    /**
     * Create a currency from json.
     *
     * @param $json
     * @return mixed
     */
    protected function createCurrencyFromJson($json)
    {
        return json_decode($json, true);
    }

    /**
     * @param $countryCode
     */
    protected function createHydrated($countryCode)
    {
        if (! isset($this->repository->countries[$countryCode]['hydrated'])) {
            $this->repository->countries[$countryCode]['hydrated'] = [];
        }

        return $this->repository->countries[$countryCode]['hydrated'];
    }

    /**
     * Hydrate elements of a collection of countries.
     *
     * @param $countries
     * @param $elements
     * @return mixed
     */
    private function hydrateCountries($countries, $elements)
    {
        return $this->repository->collection(
            $countries->map(function ($country) use ($elements) {
                return $this->hydrateCountry($country, $elements);
            })
        );
    }

    /**
     * Hydrate elements of a country.
     *
     * @param $country
     * @param $elements
     * @return mixed
     */
    private function hydrateCountry($country, $elements)
    {
        $country = $this->toArray($country);

        $countryCode = $country['cca3'];

        $this->addCountry($countryCode, $country);

        foreach ($elements as $element => $enabled) {
            $this->hydrateCountryElement($countryCode, $element, $enabled);
        }

        return $this->getCountry($countryCode);
    }

    /**
     * Check if an element is a country.
     *
     * @param $element
     * @return bool
     */
    private function isCountry($element)
    {
        return ($element instanceof Coollection || is_array($element)) && isset($element['cca3']);
    }

    /**
     * Check it's a currency array or code.
     *
     * @param $data
     * @return bool
     */
    private function isCurrencyArray($data)
    {
        return is_array($data) && isset($data['ISO4217Code']);
    }

    /**
     * Check if an element needs hydrated.
     *
     * @param $countryCode
     * @param $element
     * @param bool $enabled
     * @return bool
     */
    protected function needsHydration($countryCode, $element, $enabled = false)
    {
        if (! $this->canHydrate($element, $enabled, $countryCode)) {
            return false;
        }

        return $this->updateHydrated($countryCode, $element);
    }

    /**
     * Hydrate a collection, making a collection of collections.
     *
     * @param $country
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    protected function hydrateCollection($country)
    {
        return $this->repository->collection($country);
    }

    /**
     * Hydrate states.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateStates($country)
    {
        $country['states'] = json_decode($this->repository->getStatesJson($country), true);

        return $country;
    }

    /**
     * Hydrate topoloy.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateTopology($country)
    {
        $country['topology'] = $this->repository->getTopology($country);

        return $country;
    }

    /**
     * Hydrate geometry.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateGeometry($country)
    {
        $country['geometry'] = $this->repository->getGeometry($country);

        return $country;
    }

    /**
     * Get hydration elements.
     *
     * @param $elements
     * @return array|mixed
     */
    protected function getHydrationElements($elements)
    {
        if (! is_array($elements = $elements ?: config('countries.hydrate.elements'))) {
            return [$elements => true];
        }

        return $this->checkHydrationElements($elements);
    }

    /**
     * Hydrate flag.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateFlag($country)
    {
        $country['flag'] = $this->repository->makeAllFlags($country);

        return $country;
    }

    /**
     * Hydrate borders.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateBorders($country)
    {
        $country['borders'] = coollect($country['borders'])->map(function ($border) {
            $border = $this->repository->call('where', ['cca3', $border]);

            if ($border instanceof Coollection && $border->count() == 1) {
                return $border->first();
            }

            return $border;
        });

        return $this->toArray($country);
    }

    /**
     * Hydrate timezone.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateTimezone($country)
    {
        if (! isset($this->repository->timezones[$country['cca2']])) {
            return $country;
        }

        $country['timezone'] = $this->repository->timezones[$country['cca2']];

        return $this->toArray($country);
    }

    /**
     * Hydrate currency.
     *
     * @param $country
     * @return mixed
     */
    protected function hydrateCurrency($country)
    {
        $country['currency'] = coollect($country['currency'])->mapWithKeys(function ($code, $key) {
            if ($this->isCurrencyArray($code)) {
                return [
                    $code['ISO4217Code'] => $code,
                ];
            }

            return [
                $code => $this->repository->currenciesRepository->loadCurrency($code),
            ];
        });

        return $this->toArray($country);
    }

    /**
     * Hydrate a countries collection with languages.
     *
     * @param \PragmaRX\Coollection\Package\Coollection|array|\stdClass $target
     * @param null $elements
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function hydrate($target, $elements = null)
    {
        $elements = $this->getHydrationElements($elements);

        return ! $this->isCountry($this->toArray($target))
            ? $this->hydrateCountries($target, $elements)
            : $this->hydrateCountry($target, $elements);
    }

    /**
     * Get country by country code.
     *
     * @param $countryCode
     * @return mixed
     */
    public function getCountry($countryCode)
    {
        return $this->repository->countries[$countryCode];
    }

    /**
     * Check and create a country in the repository.
     *
     * @param $country
     * @param $countryCode
     */
    public function addCountry($countryCode, $country)
    {
        if (! isset($this->repository->countries[$countryCode])) {
            $this->repository->countries[$countryCode] = $country;
        }
    }

    /**
     * Hydrate a country element.
     *
     * @param $countryCode
     * @param $element
     * @param $enabled
     */
    public function hydrateCountryElement($countryCode, $element, $enabled)
    {
        if ($this->needsHydration($countryCode, $element, $enabled)) {
            $this->repository->countries[$countryCode] = $this->{'hydrate'.Str::studly($element)}($this->repository->countries[$countryCode]);
        }
    }

    /**
     * Check hydration elements.
     *
     * @param $elements
     * @return static
     */
    protected function checkHydrationElements($elements)
    {
        $elements = coollect($elements)->mapWithKeys(function ($value, $key) {
            if (is_numeric($key)) {
                $key = $value;
                $value = true;
            }

            return [$key => $value];
        });

        return $elements;
    }

    /**
     * Repository setter.
     *
     * @param $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Transform a class into an array.
     *
     * @param $data
     * @return mixed
     */
    public function toArray($data)
    {
        if (is_array($data)) {
            return $data;
        }

        return $data instanceof \stdClass
            ? json_decode(json_encode($data), true)
            : $data->toArray();
    }

    /**
     * Update hydrated.
     *
     * @param $countryCode
     * @param $element
     * @return bool
     */
    protected function updateHydrated($countryCode, $element)
    {
        $hydrated = $this->createHydrated($countryCode);

        $hydrated[$element] = true;

        $this->repository->countries[$countryCode]['hydrated'] = $hydrated;

        return true;
    }
}
