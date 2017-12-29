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
    protected $repository;

    /**
     * Service constructor.
     *
     * @param CountriesRepository $repository
     */
    public function __construct(CountriesRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all currencies.
     *
     * @return Coollection
     */
    public function currencies()
    {
        return countriesCollect($this->repository->currencies())->unique()->sort();
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
        $result = $this->repository->call($name, $arguments);

        if (config('countries.hydrate.after')) {
            $result = $this->repository->hydrate($result);
        }

        return $result;
    }

    /**
     * Repository getter.
     *
     * @return CountriesRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
