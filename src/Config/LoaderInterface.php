<?php namespace Config;

interface LoaderInterface {
    
    public function load($environment, $group);
    
    public function exists($group);
    
}