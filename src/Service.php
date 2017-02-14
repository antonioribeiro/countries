<?php

namespace PragmaRX\Countries;

use PragmaRX\Countries\Support\CountriesRepository;

class Service
{
    /**
     * Countries repository.
     *
     * @var CountriesRepository
     */
    protected $countriesRepository;

    /**
     * Service constructor.
     *
     * @param CountriesRepository $countriesRepository
     */
    public function __construct(CountriesRepository $countriesRepository)
    {
        $this->countriesRepository = $countriesRepository;
    }

    /**
     * Call a method.
     *
     * @param $name
     * @param array $arguments
     * @return bool|mixed
     */
    public function __call($name, array $arguments = [])
    {
        $result = $this->countriesRepository->call($name, $arguments);

        if (config('countries.hydrate.after')) {
            $result = $this->countriesRepository->hydrate($result);
        }

        return $result;
    }
}
