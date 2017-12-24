<?php

namespace PragmaRX\Countries\Package;

use PragmaRX\Coollection\Package\Coollection;
use PragmaRX\Countries\Package\Support\CountriesRepository;

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
     * Get the countries repository.
     *
     * @return CountriesRepository
     */
    public function getRepository()
    {
        return $this->countriesRepository;
    }

    /**
     * Get all currencies.
     *
     * @return Coollection
     */
    public function currencies()
    {
        return coollect($this->countriesRepository->currencies())->unique()->sort();
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
