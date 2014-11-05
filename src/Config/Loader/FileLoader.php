<?php namespace Config\Loader;

class FileLoader extends LoaderAbstract
{
    /**
     * The default configuration path.
     *
     * @var string
     */
    protected $path;


    /**
     * Create a new file configuration loader.
     *
     * @param  string $path
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Read the file and parse it returning the read array
     *
     * @param array $pathParts
     * @param $group
     * @return mixed
     */
    protected function readConfig(array $pathParts, $group)
    {
        $buildPath = implode(DIRECTORY_SEPARATOR, $pathParts) . DIRECTORY_SEPARATOR;

        $file = "{$this->path}{$buildPath}{$group}.php";

        if (is_file($file)) {
            return include($file);
        }
    }

}
