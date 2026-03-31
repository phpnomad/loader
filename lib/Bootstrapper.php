<?php

namespace PHPNomad\Loader;

use PHPNomad\Loader\Interfaces\HasClassDefinitions;
use PHPNomad\Facade\Interfaces\HasFacades;
use PHPNomad\Di\Interfaces\InstanceProvider;
use PHPNomad\Loader\Interfaces\HasLoadCondition;
use PHPNomad\Loader\Interfaces\Loadable;
use PHPNomad\Loader\Traits\CanLoadInitializers;

class Bootstrapper implements Loadable
{
    use CanLoadInitializers;

    public function __construct(InstanceProvider $container, ...$initializers)
    {
        $this->container = $container;
        $this->initializers = $initializers;
    }

    /**
     * @inheritDoc
     */
    public function load(): void
    {
        $this->loadInitializers();
    }
}
