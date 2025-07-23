<?php

namespace PragmaRX\Countries\Package\Support;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection as LaravelCollection;

class Collection extends LaravelCollection
{
    /**
     * Magic call methods.
     */
    public function __call($method, $parameters): mixed
    {
        if ($method !== 'where' && Str::startsWith($method, 'where')) {
            $method = strtolower(preg_replace('/([A-Z])/', '.$1', lcfirst(substr($method, 5))));
            if (\count($parameters) === 2) {
                return $this->where($method, $parameters[0], $parameters[1]);
            }

            if (\count($parameters) === 1) {
                return $this->where($method, $parameters[0]);
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Magic get method for object-like property access.
     */
    public function __get($key): mixed
    {
        return $this->getProperty($key);
    }

    /**
     * Magic set method for object-like property setting.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * Magic isset method to check if property exists.
     */
    public function __isset(string $key): bool
    {
        return $this->hasProperty($key);
    }

    /**
     * Determine if an item exists at an offset.
     */
    public function offsetExists($key): bool
    {
        return $this->hasProperty($key);
    }

    /**
     * Get an item at a given offset.
     */
    public function offsetGet($key): mixed
    {
        // For array access, use the original Coollection behavior
        if (!isset($this->items[$key])) {
            return null;
        }

        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Get property with case-insensitive and dynamic name support.
     */
    protected function getProperty(string $key): mixed
    {
        // Try to find the key using various case transformations
        $foundKey = $this->getArrayKey($key);

        if ($foundKey !== null) {
            return $this->wrapValue($this->items[$foundKey]);
        }

        return null;
    }

    /**
     * Get an array key using various case transformations.
     */
    protected function getArrayKey(string $key): ?string
    {
        $cases = [
            $key,
            Str::snake($key),
            strtolower(Str::snake($key)),
            Str::camel($key),
            Str::kebab($key),
            strtolower(Str::kebab($key)),
            str_replace('_', ' ', Str::snake($key)),
            strtolower(str_replace('_', ' ', Str::snake($key))),
            strtolower($key),
        ];

        foreach ($this->items as $itemKey => $value) {
            if (in_array($itemKey, $cases) || in_array(strtolower($itemKey), $cases)) {
                return $itemKey;
            }
        }

        // Dynamic property name conversion (e.g., europe_paris -> Europe/Paris)
        $convertedKey = $this->convertDynamicKey($key);
        if ($convertedKey) {
            foreach ($this->items as $itemKey => $value) {
                if ($itemKey === $convertedKey || strtolower($itemKey) === strtolower($convertedKey)) {
                    return $itemKey;
                }
            }
        }

        return null;
    }

    /**
     * Check if property exists with case-insensitive and dynamic name support.
     */
    protected function hasProperty(string $key): bool
    {
        // Direct key access
        if (array_key_exists($key, $this->items)) {
            return true;
        }

        // Case-insensitive access
        foreach ($this->items as $itemKey => $value) {
            if (strtolower((string) $itemKey) === strtolower($key)) {
                return true;
            }
        }

        // Dynamic property name conversion
        $convertedKey = $this->convertDynamicKey($key);
        if ($convertedKey && array_key_exists($convertedKey, $this->items)) {
            return true;
        }

        // Check for converted key case-insensitive
        if ($convertedKey) {
            foreach ($this->items as $itemKey => $value) {
                if (strtolower((string) $itemKey) === strtolower($convertedKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Convert dynamic property names (e.g., europe_paris -> Europe/Paris).
     */
    protected function convertDynamicKey(string $key): ?string
    {
        // Convert underscore to slash for timezone-like keys
        if (strpos($key, '_') !== false) {
            $parts = explode('_', $key);
            if (count($parts) === 2) {
                return ucfirst($parts[0]) . '/' . ucfirst($parts[1]);
            }
        }

        return null;
    }

    /**
     * Wrap value in Collection if it's an array.
     */
    protected function wrapValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return new static($value);
        }

        return $value;
    }

    /**
     * Get the values of a given key.
     */
    public function pluck($value, $key = null): Collection
    {
        $result = parent::pluck($value, $key);

        // Wrap array values in Collections
        return $result->map(function ($item) {
            return $this->wrapValue($item);
        });
    }

    /**
     * Get the first item from the collection.
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        $result = parent::first($callback, $default);

        if (is_array($result)) {
            return new static($result);
        }

        // If result is null and this collection is empty, return an empty collection
        // This maintains backward compatibility with Coollection behavior
        if ($result === null && $this->isEmpty()) {
            return new static([]);
        }

        return $result;
    }

    /**
     * Execute a callback over each item.
     */
    public function each(callable $callback): Collection
    {
        foreach ($this->items as $key => $item) {
            $wrappedItem = is_array($item) ? new static($item) : $item;

            if ($callback($wrappedItem, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Overwrite the collection with new data.
     */
    public function overwrite(mixed $data): Collection
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            return $this;
        }

        $this->items = array_merge($this->items, $data);

        return $this;
    }

    /**
     * Recursively sort the collection by keys.
     */
    public function sortByKeysRecursive(): Collection
    {
        $items = $this->toArray();
        array_sort_by_keys_recursive($items);

        return new static($items);
    }

    /**
     * Sort the collection by keys.
     */
    public function sortByKey(): Collection
    {
        $items = $this->items;
        ksort($items);

        return new static($items);
    }

    /**
     * Hydrate configured default elements.
     */
    public function hydrateDefaultElements(Collection $countries): static
    {
        // For now, just return the countries as-is
        // The hydrate functionality will be handled by the hydrator service
        return $countries instanceof static ? $countries : new static($countries->toArray());
    }

    /**
     * Where on steroids.
     */
    public function where($key, $operator = null, $value = null): static
    {
        if (\func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $countries = method_exists($this, 'where' . ucfirst($key))
            ? $this->{'where' . ucfirst($key)}($value)
            : parent::where($key, $operator, $value);

        return $this->hydrateDefaultElements($countries);
    }

    /**
     * Where language.
     */
    public function whereLanguage(mixed $value): static
    {
        return $this->_whereAttribute('languages', $value);
    }

    /**
     * Where language using iso code.
     */
    public function whereISO639_3(mixed $value): static
    {
        return $this->_whereKey('languages', $value);
    }

    /**
     * Where currency using ISO code.
     */
    public function whereISO4217(mixed $value): static
    {
        return $this->_whereAttribute('currency', $value);
    }

    /**
     * Where for different attributes.
     */
    private function arrayFinder(string $propertyName, mixed $find, Closure $finderClosure): static
    {
        return $this->filter(function (mixed $data) use ($find, $propertyName, $finderClosure): mixed {
            try {
                // Handle both array and object access
                if (is_array($data)) {
                    $attributeValue = $data[$propertyName] ?? null;
                } else {
                    $attributeValue = $data->{$propertyName};
                }
            } catch (\Exception) {
                $attributeValue = null;
            }

            return \is_null($attributeValue) ? null : $finderClosure($find, $attributeValue, $data);
        });
    }

    /**
     * Where for keys.
     */
    private function _whereKey(string $arrayName, mixed $value): static
    {
        $finderClosure = function (mixed $value, mixed $attributeValue): bool {
            return Arr::has($attributeValue, $value);
        };

        return $this->hydrateDefaultElements($this->arrayFinder($arrayName, $value, $finderClosure));
    }

    /**
     * Where for different attributes.
     */
    private function _whereAttribute(string $arrayName, mixed $value): static
    {
        $finderClosure = function (mixed $value, mixed $attributeValue): bool {
            // Handle both array and Collection types
            if (is_array($attributeValue)) {
                return \in_array($value, $attributeValue);
            } elseif ($attributeValue instanceof Collection) {
                return \in_array($value, $attributeValue->toArray());
            } elseif (is_object($attributeValue) && method_exists($attributeValue, 'toArray')) {
                return \in_array($value, $attributeValue->toArray());
            }

            return false;
        };

        return $this->hydrateDefaultElements($this->arrayFinder($arrayName, $value, $finderClosure));
    }
}
