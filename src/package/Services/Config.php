<?php

namespace PragmaRX\Countries\Package\Support;

class Config
{
    /**
     * Configuration.
     *
     * @var \PragmaRX\Coollection\Package\Coollection
     */
    protected $config;

    /**
     * Config constructor.
     *
     * @param array|null $config
     */
    public function __construct($config = null)
    {
        $this->initialize($config);
    }

    /**
     * @param $key
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function get($key)
    {
        return $this->config->get($key);
    }

    /**
     * @param $config
     */
    protected function initialize($config = [])
    {
        $this->config = $this->loadConfig()->overwrite($config);
    }

    /**
     * Load the config.
     *
     * @return Collection
     */
    protected function loadConfig()
    {
        return countriesCollect(
            require __DIR__.'/../../config/countries.php'
        );
    }

    /**
     * Redirect properties access to config's Coollection.
     *
     * @param $name
     * @return mixed|static
     */
    public function __get($name)
    {
        return $this->config->{$name};
    }

    /**
     * Redirect methods calls to config's Coollection.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->config, $name], $arguments);
    }
}
