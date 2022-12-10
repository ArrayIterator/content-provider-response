<?php
declare(strict_types=1);

namespace Arrayiterator\AggregatorCpSdk;

use Slim\App;

abstract class Endpoint
{
    /**
     * @var App
     */
    protected $app;
    /**
     * @var bool
     */
    private $registered = false;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    final public function register()
    {
        if ($this->registered) {
            return;
        }
        $this->registered = true;
        $this->registerRoute($this->app);
    }

    abstract public function registerRoute(App $app);
}
