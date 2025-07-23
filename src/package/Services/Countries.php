<?php

namespace PragmaRX\Countries\Package\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Countries\Package\Data\Repository;
use PragmaRX\Countries\Package\Services\Cache\Service as Cache;

class Countries
{
    /**
     * Countries repository.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Helper.
     *
     * @var Helper
     */
    protected $helper;

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * Service constructor.
     *
     * @param object     $config
     * @param Cache      $cache
     * @param Helper     $helper
     * @param Hydrator   $hydrator
     * @param Repository $repository
     */
    public function __construct(
        $config = null,
        ?Cache $cache = null,
        ?Helper $helper = null,
        ?Hydrator $hydrator = null,
        ?Repository $repository = null,
    ) {
        $a = new \PragmaRX\Countries\Package\Services\Cache\Service();

        $this->helper = $this->instantiateHelper($helper);

        $this->config = $this->instantiateConfig($config);

        $this->cache = $this->instantiateCache($cache);

        $this->hydrator = $this->instantiateHydrator($hydrator);

        $this->repository = $this->instantiateRepository($repository);

        $this->hydrator->setRepository($this->repository);

        $this->init();
    }

    /**
     * Call a method.
     *
     * @param       string $name
     * @param array $arguments
     *
     * @return bool|mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        $result = $this->repository->call($name, $arguments);

        if ($this->config->get('hydrate.after')) {
            $result = $this->repository->hydrate($result);
        }

        return $result;
    }

    private function createCollectionMacros(): void
    {
        $instance = $this;

        Collection::macro('hydrate', function ($elements = null) use ($instance) {
            return $instance->hydrate($this, $elements);
        });

        foreach (Hydrator::HYDRATORS as $hydrator) {
            $hydrator = 'hydrate' . Str::studly($hydrator);

            Collection::macro($hydrator, function () use ($hydrator, $instance) {
                /** @phpstan-ignore-next-line */
                $result = $instance->getRepository()->getHydrator()->{$hydrator}($this->toArray());
                return countriesCollect($result);
            });
        }
    }

    /**
     * Get all currencies.
     *
     * @return Collection
     */
    public function currencies()
    {
        return countriesCollect($this->repository->currencies())
            ->unique()
            ->sort();
    }

    /**
     * Cache instance getter.
     *
     * @return Cache|Config
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get the config instance.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Repository getter.
     *
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Hydrate a collection with specified elements.
     *
     * @param mixed $collection
     * @param array|null $elements
     * @return mixed
     */
    public function hydrate($collection, $elements = null)
    {
        return $this->repository->hydrate($collection, $elements);
    }

    /**
     * Initialize class.
     */
    protected function init(): void
    {
        $this->createCollectionMacros();

        $this->defineConstants();

        $this->repository->boot();
    }

    /**
     * Instantiate cache.
     *
     * @param Cache|null $cache
     *
     * @return Cache
     */
    protected function instantiateCache(?Cache $cache = null)
    {
        if (\is_null($this->cache) || !\is_null($cache)) {
            $this->cache = !\is_null($cache) ? $cache : new Cache($this->config);
        }

        return $this->cache;
    }

    /**
     * Instantiate config.
     *
     * @param object|null $config
     *
     * @return Config
     */
    protected function instantiateConfig($config = null)
    {
        if (\is_null($this->config) || !\is_null($config)) {
            $this->config = !\is_null($config) ? $config : new Config($this->helper);
        }

        return $this->config;
    }

    /**
     * @param Helper|null $helper
     *
     * @return Helper
     */
    protected function instantiateHelper(?Helper $helper = null)
    {
        $this->helper = \is_null($helper)
            ? (\is_null($this->helper)
                ? ($this->helper = new Helper($this->instantiateConfig()))
                : $this->helper)
            : $helper;

        return $this->helper;
    }

    /**
     * Instantiate hydrator.
     *
     * @param Hydrator|null $hydrator
     *
     * @return Hydrator
     */
    protected function instantiateHydrator(?Hydrator $hydrator = null)
    {
        if (\is_null($this->hydrator) || !\is_null($hydrator)) {
            $this->hydrator = !\is_null($hydrator) ? $hydrator : new Hydrator($this->config);
        }

        return $this->hydrator;
    }

    /**
     * @param Repository|null $repository
     *
     * @return Repository
     */
    protected function instantiateRepository($repository)
    {
        if (\is_null($repository)) {
            $repository = new Repository(
                $this->instantiateCache(),
                $this->instantiateHydrator(),
                $this->instantiateHelper(),
                $this->instantiateConfig(),
            );
        }

        return $repository;
    }

    public function defineConstants(): void
    {
        if (!\defined('__COUNTRIES_DIR__')) {
            \define('__COUNTRIES_DIR__', realpath(__DIR__ . $this->helper->toDir('/../../../')));
        }
    }
}
