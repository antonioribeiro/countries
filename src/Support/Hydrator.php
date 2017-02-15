<?php

namespace PragmaRX\Countries\Support;

use Illuminate\Support\Str;

class Hydrator
{
    /**
     * Countries repository.
     *
     * @var
     */
    protected $repository;

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
     * Check if an element needs hydrated.
     *
     * @param $cc
     * @param $element
     * @param bool $enabled
     * @return bool
     */
    protected function needsHydration($cc, $element, $enabled = false)
    {
        if (! $enabled && ! config('countries.hydrate.elements.'.$element)) {
            return false;
        }

        if (! isset($this->repository->countries[$cc]['hydrated'])) {
            $this->repository->countries[$cc]['hydrated'] = [];
        }

        if (isset($this->repository->countries[$cc]['hydrated'][$element])) {
            return false;
        }

        $hydrate = $this->repository->countries[$cc]['hydrated'];

        $hydrate[$element] = true;

        $this->repository->countries[$cc]['hydrated'] = $hydrate;

        return true;
    }

    /**
     * Hydrate a collection, making a collection of collections.
     *
     * @param $country
     * @return Collection
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
        $country['borders'] = collect($country['borders'])->map(function($border) {
            $border = $this->repository->call('where', ['cca3', $border]);

            if ($border instanceof Collection && $border->count() == 1) {
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
        $country['currency'] = collect($country['currency'])->map(function($code) {
            return $this->repository->currenciesRepository->loadCurrency($code);
        });

        return $this->toArray($country);
    }

    /**
     * Hydrate a countries collection with languages.
     *
     * @param Collection $countries
     * @param null $elements
     * @return Collection
     */
    public function hydrate(Collection $countries, $elements = null)
    {
        $elements = $this->getHydrationElements($elements);

        return $this->repository->collection(
            $countries->map(function($country) use ($elements) {
                $country = $this->toArray($country);

                if (! isset($this->repository->countries[$cc = $country['cca3']])) {
                    $this->repository->countries[$cc] = $country;
                }

                foreach ($elements as $element => $enabled) {
                    if ($this->needsHydration($cc, $element, $enabled)) {
                        $this->repository->countries[$cc] = $this->{'hydrate'.Str::studly($element)}($this->repository->countries[$cc]);
                    }
                }

                return $this->repository->countries[$cc];
            })
        );
    }

    /**
     * Check hydration elements.
     *
     * @param $elements
     * @return static
     */
    protected function checkHydrationElements($elements)
    {
        $elements = collect($elements)->mapWithKeys(function ($value, $key) {
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
        if ($data instanceof \stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        return $data;
    }
}
