<?php

namespace Folklore\Mediatheque\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use \JsonSerializable;

abstract class Definition implements JsonSerializable, Arrayable, Jsonable
{
    public function __construct($definition = [])
    {
        if (!is_null($definition)) {
            $this->setDefinition($definition);
        }
    }

    public function setDefinition($definition)
    {
        foreach ($definition as $key => $value) {
            $propertyName = Str::camel($key);
            if ($this->hasProperty($propertyName)) {
                $methodName = 'set'.Str::studly($key);
                $this->{$methodName}($value);
            }
        }
        return $this;
    }

    public function set($key, $value)
    {
        $this->{$key} = $value;
        return $this;
    }

    public function get($key)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        return $this->{$key}();
    }

    protected function hasProperty($key)
    {
        return property_exists($this, $key) || method_exists($this, $key);
    }

    abstract public function toArray();

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __call($name, $arguments)
    {
        if (preg_match('/^(set|get)([A-Z].*)$/', $name, $matches)) {
            $method = $matches[1];
            $property = Str::camel($matches[2]);
            if ($this->hasProperty($property)) {
                array_unshift($arguments, $property);
                return call_user_func_array([$this, $method], $arguments);
            }
        }
    }
}