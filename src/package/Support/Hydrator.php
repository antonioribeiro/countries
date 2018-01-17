<?php

namespace PragmaRX\Countries\Package\Support;

use Illuminate\Support\Str;
use PragmaRX\Coollection\Package\Coollection;

class Hydrator extends Base
{
    /**
     * All hydrators.
     *
     * @var
     */
    const HYDRATORS = [
        'borders',
        'cities',
        'collection',
        'countries',
        'country',
        'currencies',
        'flag',
        'geometry',
        'natural_earth_data',
        'states',
        'taxes',
        'timezones',
        'topology',
    ];

    /**
     * Countries repository.
     *
     * @var CountriesRepository
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
    private function hydrateCountries($countries, $elements = null)
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
     * Check it's a currencies array or code.
     *
     * @param $data
     * @return bool
     */
    private function isCurrenciesArray($data)
    {
        return is_array($data) && isset($data['ISO4217Code']);
    }

    /**
     * Load the cities file and merge overloads.
     *
     * @param $country
     * @return mixed
     */
    private function loadCities($country)
    {
        return $this->repository->getHelper()->loadJson($country['cca3'], 'cities/default')
                ->overwrite($this->repository->getHelper()->loadJson($country['cca3'], 'cities/overload'));
    }

    /**
     * Load the states file and merge overloads.
     *
     * @param $country
     * @return mixed
     */
    private function loadStates($country)
    {
        return $this->repository->getHelper()->loadJson($country['cca3'], 'states/default')
                ->overwrite($this->repository->getHelper()->loadJson($country['cca3'], 'states/overload'));
    }

    /**
     * Load the taxes file and merge overloads.
     *
     * @param $country
     * @return mixed
     */
    private function loadTaxes($country)
    {
        return $this->repository->getHelper()->loadJson($country['cca3'], 'taxes/default')
                                ->overwrite($this->repository->getHelper()->loadJson($country['cca3'], 'taxes/overload'));
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
     * Hydrate cities.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateCities($country)
    {
        $country['cities'] = $this->loadCities($country);

        return $country;
    }

    /**
     * Hydrate states.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateStates($country)
    {
        $country['states'] = $this->loadStates($country);

        return $country;
    }

    /**
     * Hydrate taxes.
     *
     * @param $country
     * @return Coollection
     */
    public function hydrateTaxes($country)
    {
        $country['taxes'] = $this->loadTaxes($country);

        return $country;
    }

    /**
     * Hydrate topoloy.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateTopology($country)
    {
        $country['topology'] = $this->repository->getTopology($country['cca3']);

        return $country;
    }

    /**
     * Hydrate geometry.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateGeometry($country)
    {
        $country['geometry'] = $this->repository->getGeometry($country['cca3']);

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
    public function hydrateFlag($country)
    {
        $country = countriesCollect($country)->overwrite(
            ['flag' => $this->repository->makeAllFlags($country)]
        );

        return $country;
    }

    /**
     * Hydrate borders.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateBorders($country)
    {
        $country['borders'] = isset($country['borders'])
            ? $country['borders'] = countriesCollect($country['borders'])->map(function ($border) {
                return $this->repository->call('where', ['cca3', $border])->first();
            })
            : countriesCollect();

        return $this->toArray($country);
    }

    /**
     * Hydrate timezones.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateTimezones($country)
    {
        return $country->overwrite(['timezones' => $this->repository->findTimezones($country['cca3'])]);
    }

    /**
     * Hydrate currencies.
     *
     * @param $country
     * @return mixed
     */
    public function hydrateCurrencies($country)
    {
        $currencies = [];

        if (isset($country['currencies'])) {
            $currencies = countriesCollect($country['currencies'])->mapWithKeys(function ($code, $key) {
                if ($this->isCurrenciesArray($code)) {
                    return [
                        $code['ISO4217Code'] => $code,
                    ];
                }

                return [
                    $code => $this->repository->loadCurrenciesForCountry($code),
                ];
            });
        }

        $country['currencies'] = $currencies;

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
        return countriesCollect($this->repository->countries[$countryCode]);
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
        $elements = countriesCollect($elements)->mapWithKeys(function ($value, $key) {
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
        if (is_array($data) || is_null($data)) {
            return $data;
        }

        return $data->toArray();
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
