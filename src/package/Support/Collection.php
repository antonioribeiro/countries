<?php

namespace PragmaRX\Countries\Package\Support;

use Illuminate\Support\Arr;
use PragmaRX\Coollection\Package\Coollection;

class Collection extends Coollection
{
    /**
     * Magic call methods.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed|static
     */
    public function __call($name, $arguments)
    {
        if (starts_with($name, 'where')) {
            $name = strtolower(preg_replace('/([A-Z])/', '.$1', lcfirst(substr($name, 5))));
            if (count($arguments) == 2) {
                return $this->where($name, $arguments[0], $arguments[1]);
            } elseif (count($arguments) == 1) {
                return $this->where($name, $arguments[0]);
            }
        }

        return parent::__call($name, $arguments);
    }

    /**
     * Where on steroids.
     *
     * @param string $key
     * @param mixed $operator
     * @param null $value
     * @return static
     */
    public function where($key, $operator, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;

            $operator = '=';
        }

        if (array_key_exists($key, config('countries.maps'))) {
            $key = config('countries.maps')[$key];
        }

        if (method_exists($this, 'where'.ucfirst($key))) {
            return $this->{'where'.ucfirst($key)}($value);
        }

        return parent::where($key, $operator, $value);
    }

    /**
     * Where language.
     *
     * @param $value
     * @return static
     */
    public function whereLanguage($value)
    {
        return $this->_whereAttribute('languages', $value);
    }

    /**
     * Where language using iso code.
     *
     * @param $value
     * @return static
     */
    public function whereISO639_3($value)
    {
        return $this->_whereKey('languages', $value);
    }

    /**
     * Where currency using ISO code.
     *
     * @param $value
     * @return static
     */
    public function whereISO4217($value)
    {
        return $this->_whereAttribute('currency', $value);
    }

    /**
     * Where for different attributes.
     *
     * @param string $arrayName
     * @param $value
     * @return static
     */
    private function _whereAttribute(string $arrayName, $value)
    {
        return $this->filter(function ($data) use ($value, $arrayName) {
            if (isset($data->{$arrayName})) {
                return in_array($value, (array) $data->{$arrayName});
            }

            return false;
        });
    }

    /**
     * Where for keys.
     *
     * @param string $arrayName
     * @param $value
     * @return static
     */
    private function _whereKey(string $arrayName, $value)
    {
        return $this->filter(function ($data) use ($value, $arrayName) {
            if (isset($data->{$arrayName})) {
                return Arr::has($data->{$arrayName}, $value);
            }

            return false;
        });
    }
}
