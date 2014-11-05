<?php namespace Config\Loader;

abstract class LoaderAbstract implements LoaderInterface
{
    
    /**
     * Load the given configuration group.
     *
     * @param  string $environment
     * @param  string $group
     * @return array
     */
    public function load($environment, $group)
    {
        $envParts = array();

        $items = array();

        foreach ($this->parseEnvironment($environment) as $env) {

            $envParts[] = $env;

            // Loop through the directories down the environment name, checking for the environment specific
            // configuration files which will be merged on top of the previous files arrays so that they get
            // precedence over them if we are currently in an environments setup.
            if (($envItems = $this->readConfig($envParts, $group)) !== null) {
                $items = $this->mergeEnvironment($items, $envItems);
            }
        }

        return $items ?: null;
    }

    abstract protected function readConfig(array $pathParts, $group);

    /**
     * Split the environment at dots or slashes creating an array of namespaces to look through
     *
     * @param  string $environment
     * @return array
     */
    protected function parseEnvironment($environment)
    {
        $environments = array_filter(preg_split('/(\/|\.)/', $environment));

        array_unshift($environments, '');

        return $environments;
    }

    /**
     * @param array $items1
     * @param array $items2
     * @return array
     */
    protected function mergeEnvironment(array $items1, array $items2)
    {
        return array_replace_recursive($items1, $items2);
    }

}
