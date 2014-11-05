<?php namespace Config\Loader;

class CallbackLoader implements LoaderInterface
{
    /**
     * The default configuration path.
     *
     * @var string
     */
    protected $callback;

    /**
     * @param callable $callback
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param $environment
     * @param $group
     * @return mixed
     */
    public function load($environment, $group)
    {
        return call_user_func($this->callback, $environment, $group);
    }

}
