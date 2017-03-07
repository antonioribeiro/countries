<?php

namespace PragmaRX\Countries\Support;

use Exception;
use Illuminate\Support\Arr;
use PragmaRX\Countries\Facade as CountriesFacade;
use Illuminate\Support\Collection as IlluminateCollection;

class Collection extends IlluminateCollection
{
    /**
     * Collection constructor.
     * @param array $items
     */
    public function __construct($items = [])
    {
        parent::__construct($items);

        $this->createMacros();
    }

    /**
     * Take the first item.
     *
     * @param callable|null $callback
     * @param null $default
     * @return mixed|Collection
     */
    public function first(callable $callback = null, $default = null)
    {
        return $this->make(parent::first($callback, $default));
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        return $this->make(parent::pop());
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable  $callback
     * @param  mixed  $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        $this->make(parent::reduce($callback, $initial));
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        return $this->make(parent::shift());
    }

    /**
     * Create collection macros.
     */
    private function createMacros()
    {
        static::macro('hydrate', function ($elements) {
            return CountriesFacade::hydrate($this, $elements);
        });
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        if (isset($this->items[$key])) {
            if (is_array($this->items[$key])) {
                return $this->make($this->items[$key]);
            }

            return $this->items[$key];
        }

        if (! in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }

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

    public function whereLanguage($value)
    {
        return $this->_whereAttribute('languages', $value);
    }

    public function whereISO639_3($value)
    {
        return $this->_whereKey('languages', $value);
    }

    public function whereISO4217($value)
    {
        return $this->_whereAttribute('currency', $value);
    }

    private function _whereAttribute(string $arrayName, $value)
    {
        return $this->filter(function ($data) use ($value, $arrayName) {
            if (isset($data->{$arrayName})) {
                return in_array($value, (array) $data->{$arrayName});
            }

            return false;
        });
    }

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
