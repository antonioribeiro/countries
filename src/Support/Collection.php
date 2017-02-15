<?php

namespace PragmaRX\Countries\Support;

use Exception;
use PragmaRX\Countries\Facade as CountriesFacade;
use Illuminate\Support\Collection as IlluminateCollection;

class Collection extends IlluminateCollection
{
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

    private function createMacros()
    {
        static::macro('hydrate', function($elements) {
            return CountriesFacade::hydrate($this, $elements);
        });
    }
}
