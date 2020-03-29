<?php

namespace PragmaRX\Countries\Package;

use PragmaRX\Countries\Package\Data\Repository;
use PragmaRX\Countries\Package\Services\Cache\Service as Cache;
use PragmaRX\Countries\Package\Services\Countries as CountriesService;
use PragmaRX\Countries\Package\Services\Helper;
use PragmaRX\Countries\Package\Services\Hydrator;

class Countries
{
    /**
     * The actual Countries class is a service.
     *
     * @var CountriesService
     */
    private $countriesService;

    /**
     * Service constructor.
     *
     * @param $config
     * @param Cache $cache
     * @param Helper $helper
     * @param Hydrator $hydrator
     * @param Repository $repository
     */
    public function __construct(
        $config = null,
        Cache $cache = null,
        Helper $helper = null,
        Hydrator $hydrator = null,
        Repository $repository = null
    ) {
        $this->countriesService = new CountriesService($config, $cache, $helper, $hydrator, $repository);
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
        return \call_user_func_array([$this->countriesService, $name], $arguments);
    }

    /**
     * Translate static methods calls to dynamic.
     *
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, array $arguments = [])
    {
        return \call_user_func_array([new static(), $name], $arguments);
    }
}
