<?php namespace Config\Loader;

interface LoaderInterface
{

    public function load($environment, $group);

}