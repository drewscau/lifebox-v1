<?php

namespace App\Serializers;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class Serializer
{
    /**
     * Override static method call of serializer object
     *
     * @param  string $method
     * @param  array $arguments
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $instance = new static;

        if (! method_exists($instance, $method)) {
            throw new BadMethodCallException("Method name [{$method}] not exist or undefined.");
        }

        if (! $arguments[0]) return $arguments[0];

        return $instance->$method(...$arguments);
    }

    /**
     * Accept collection and return an array of serialized models
     *
     * @return array
     */
    private function collection(Collection $collection) : array
    {
        return $collection->map(function ($item) {
            return $this->serialize($item);
        })->all();
    }
}
