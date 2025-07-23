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
     * @param mixed      $config
     * @param Cache|null $cache
     * @param Helper|null $helper
     * @param Hydrator|null $hydrator
     * @param Repository|null $repository
     */
    public function __construct(
        mixed $config = null,
        ?Cache $cache = null,
        ?Helper $helper = null,
        ?Hydrator $hydrator = null,
        ?Repository $repository = null,
    ) {
        $this->countriesService = new CountriesService($config, $cache, $helper, $hydrator, $repository);
    }

    /**
     * Call a method.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        return \call_user_func_array([$this->countriesService, $name], $arguments);
    }

    /**
     * Translate static methods calls to dynamic.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        return \call_user_func_array([new static(), $name], $arguments);
    }
}
