<?php

namespace PragmaRX\Countries\Package\Services;

class Config
{
    /**
     * Configuration.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $config;

    /**
     * Key prefix.
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * Config constructor.
     *
     * @param array|object|null $config
     */
    public function __construct($config = null)
    {
        $this->initialize($config);
    }

    /**
     * @param string $key
     *
     * @return \Illuminate\Support\Collection|mixed|null
     */
    public function get(string $key)
    {
        // Handle dot notation for nested keys
        $keys = explode('.', $this->prefix . $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (is_object($value) && method_exists($value, 'get')) {
                $value = $value->get($segment);
            } elseif (is_array($value) && isset($value[$segment])) {
                $value = $value[$segment];
            } else {
                return null;
            }
        }

        // Wrap arrays in Collections to maintain backward compatibility
        if (is_array($value)) {
            return countriesCollect($value);
        }

        return $value;
    }

    /**
     * @param mixed $config
     */
    protected function initialize(mixed $config = []): void
    {
        if (\is_object($config)) {
            $this->config = $config;

            $this->prefix = 'countries.';
        } else {
            $this->config = $this->loadConfig()->merge($config);
        }
    }

    /**
     * Load the config.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function loadConfig()
    {
        return countriesCollect(require __DIR__ . '/../../config/countries.php');
    }

    /**
     * Redirect properties access to config's Collection.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->config->{$name};
    }

    /**
     * Redirect methods calls to config's Collection.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return \call_user_func_array([$this->config, $name], $arguments);
    }
}
