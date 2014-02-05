<?php namespace Config;

class FileLoader implements LoaderInterface {


        /**
         * The default configuration path.
         *
         * @var string
         */
        protected $path;
        
        
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
            
            $found = false;

            $buildPath = '';

            $items = array();

            foreach($this->parseEnvironment($environment) as $env){

                $buildPath .= $env . DIRECTORY_SEPARATOR;

                // Recurse through the directories down the environment specified, checking for the environment specific configuration
                // files which will be merged on top of the previous files arrays so that they get
                // precedence over them if we are currently in an environments setup.
                $environmentFile = "{$this->path}{$buildPath}{$group}.php";

                if (is_file($environmentFile))
                {
                        $items = $this->mergeEnvironment($items, $environmentFile);

                        $found = true;
                }
            }

            return $found ? $items : null;
        }
        
        protected function parseEnvironment($environment)
        {
            // Split the environment at dots or slashes
            $environments = array_filter(preg_split('/(\/|\.)/', $environment));
            
            array_unshift($environments, '');
            
            return $environments;
        }

        /**
         * Merge the items in the given file into the items.
         *
         * @param  array   $items
         * @param  string  $file
         * @return array
         */
        private function mergeEnvironment(array $items, $file)
        {
                return array_replace_recursive($items, include($file));
        }
        
        
        public function getPath()
        {
            return $this->path;
        }

}
