<?php namespace Config\Loader;

class Accessor implements AccessorInterface
{
    protected $item = array();

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function get($key)
    {
        return $this->item[$key];
    }

    public function has($key)
    {
        return array_key_exists($this->item, $key);
    }
}