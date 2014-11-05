<?php namespace Config\Loader;

class NullLoader extends LoaderAbstract
{
    /**
     * Return null
     *
     * @param array $pathParts
     * @param $group
     * @return mixed
     */
    protected function readConfig(array $pathParts, $group)
    {

    }

}
