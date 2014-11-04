<?php

class ConfigTree implements ArrayAccess
{
    protected $trees = array();

    public function get($key)
    {
        foreach ($this->trees as $tree) {
            if (isset($tree[$key])) {
                return $tree[$key];
            }
        }
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Get a configuration option.
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {

    }

    /**
     * Unset a configuration option.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {

    }
}