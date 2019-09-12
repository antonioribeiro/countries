<?php

namespace PragmaRX\Countries\Package\Support;

use Closure;
use IlluminateAgnostic\Arr\Support\Arr;
use IlluminateAgnostic\Str\Support\Str;
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
        if ($name !== 'where' && Str::startsWith($name, 'where')) {
            $name = strtolower(preg_replace('/([A-Z])/', '.$1', lcfirst(substr($name, 5))));
            if (\count($arguments) === 2) {
                return $this->where($name, $arguments[0], $arguments[1]);
            }

            if (\count($arguments) === 1) {
                return $this->where($name, $arguments[0]);
            }
        }

        return parent::__call($name, $arguments);
    }

    /**
     * Hydrate configured default elements.
     *
     * @param Collection $countries
     * @return Collection
     */
    public function hydrateDefaultElements($countries)
    {
        return $countries->hydrate();
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
        if (\func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        $countries = method_exists($this, 'where'.ucfirst($key))
            ? $this->{'where'.ucfirst($key)}($value)
            : parent::where($key, $operator, $value);

        return $this->hydrateDefaultElements($countries);
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
     * @param string $propertyName
     * @param $find
     * @param Closure $finderClosure
     * @return static
     */
    private function arrayFinder(string $propertyName, $find, Closure $finderClosure)
    {
        return $this->filter(function ($data) use ($find, $propertyName, $finderClosure) {
            try {
                $attributeValue = $data->{$propertyName};
            } catch (\Exception $e) {
                $attributeValue = null;
            }

            return \is_null($attributeValue)
                ? null
                : $finderClosure($find, $attributeValue, $data);
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
        $finderClosure = function ($value, $attributeValue) {
            return Arr::has($attributeValue, $value);
        };

        return $this->hydrateDefaultElements(
            $this->arrayFinder($arrayName, $value, $finderClosure)
        );
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
        $finderClosure = function ($value, $attributeValue) {
            return \in_array($value, $attributeValue->toArray());
        };

        return $this->hydrateDefaultElements(
            $this->arrayFinder($arrayName, $value, $finderClosure)
        );
    }
}
