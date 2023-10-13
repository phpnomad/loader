<?php

namespace Phoenix\Loader;

use Phoenix\Loader\Interfaces\HasClassDefinitions;
use Phoenix\Facade\Interfaces\HasFacades;
use Phoenix\Di\Container;
use Phoenix\Loader\Interfaces\HasLoadCondition;
use Phoenix\Loader\Interfaces\Loadable;
use Phoenix\Loader\Traits\CanLoadInitializers;

class Bootstrapper implements Loadable
{
    use CanLoadInitializers;

    protected function __construct(Container $container, ...$initializers)
    {
        $this->container = $container;
        $this->initializers = $initializers;
    }

    /**
     * @param HasClassDefinitions|Loadable|HasLoadCondition|HasFacades ...$initializers
     * @return void
     */
    public static function init(...$initializers)
    {
        $instance = new static(new Container(), ...$initializers);
        $instance->load();
    }

    /**
     * @inheritDoc
     */
    public function load(): void
    {
        $this->loadInitializers();
    }
}
