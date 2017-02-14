<?php

namespace PragmaRX\Countries\Support;

use Exception;
use Illuminate\Database\Eloquent\Collection as IlluminateDatabaseCollection;

class Collection extends IlluminateDatabaseCollection
{
    /**
    * Transform array to collection.
    *
    * @param $data
    * @return Collection|mixed
    */
    protected function makeCollection($data)
    {
        return is_array($data) ? $this->make($data) : $data;
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
        return $this->makeCollection(parent::first($callback, $default));
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        return $this->makeCollection(parent::pop());
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
        $this->makeCollection(parent::reduce($callback, $initial));
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        return $this->makeCollection(parent::shift());
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
            return $this->makeCollection($this->items[$key]);
        }

        if (! in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }
}
