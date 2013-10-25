<?php namespace Ucli\Config;

class FileLoader implements LoaderInterface {


        /**
         * The default configuration path.
         *
         * @var string
         */
        protected $path;


        /**
         * A cache of whether namespaces and groups exists.
         *
         * @var array
         */
        protected $exists = array();

        /**
         * Create a new file configuration loader.
         *
         * @param  array  $files
         * @param  string  $path
         * @return void
         */
        public function __construct($path)
        {
                $this->path = $path;
        }

        /**
         * Load the given configuration group.
         *
         * @param  string  $environment
         * @param  string  $group
         * @param  string  $namespace
         * @return array
         */
        public function load($environment, $group)
        {

                if(!$this->exists($group)) return;
                
                // First we'll get the main configuration file for the groups. Once we have
                // that we can check for any environment specific files, which will get
                // merged on top of the main arrays to make the environments cascade.
                $file = "{$this->path}/{$group}.php";

                $items = include($file);

                // Finally we're ready to check for the environment specific configuration
                // file which will be merged on top of the main arrays so that they get
                // precedence over them if we are currently in an environments setup.
                $environmentFile = "{$this->path}/{$environment}/{$group}.php";

                if (is_file($file))
                {
                        $items = $this->mergeEnvironment($items, $environmentFile);
                }

                return $items;
        }

        /**
         * Merge the items in the given file into the items.
         *
         * @param  array   $items
         * @param  string  $file
         * @return array
         */
        protected function mergeEnvironment(array $items, $file)
        {
                return array_replace_recursive($items, include($file));
        }

        /**
         * Determine if the given group exists.
         *
         * @param  string  $group
         * @param  string  $namespace
         * @return bool
         */
        public function exists($group)
        {

                // We'll first check to see if we have determined if this namespace and
                // group combination have been checked before. If they have, we will
                // just return the cached result so we don't have to hit the disk.
                if (isset($this->exists[$group]))
                {
                        return $this->exists[$group];
                }

                $file = "{$this->path}/{$group}.php";

                // Finally, we can simply check if this file exists. We will also cache
                // the value in an array so we don't have to go through this process
                // again on subsequent checks for the existing of the config file.
                
                return $this->exists[$group] = is_file($file);
        }
        
        public function getPath()
        {
            return $this->path;
        }

}