<?php

namespace Phoenix\Loader\Traits;

use Phoenix\Di\Container;
use Phoenix\Di\Interfaces\CanSetContainer;
use Phoenix\Facade\Interfaces\HasFacades;
use Phoenix\Loader\Interfaces\HasClassDefinitions;
use Phoenix\Loader\Interfaces\HasLoadCondition;
use Phoenix\Loader\Interfaces\Loadable;
use Phoenix\Utils\Helpers\Arr;

trait CanLoadInitializers
{
    protected Container $container;

    /**
     * @var array[HasClassDefinitions|Loadable|HasLoadCondition|HasFacades]
     */
    protected array $initializers = [];

    protected function loadInitializers()
    {
        foreach ($this->initializers as $initializer) {
            $this->loadInitializer($initializer);
        }
    }

    /**
     * @param HasClassDefinitions|Loadable|HasLoadCondition|HasFacades $initializer
     * @return void
     */
    protected function loadInitializer($initializer): void
    {
        if($initializer instanceof CanSetContainer){
            $initializer->setContainer($this->container);
        }

        // Bail early if this has a load condition preventing it from loading.
        if ($initializer instanceof HasLoadCondition && !$initializer->shouldLoad()) {
            return;
        }

        if ($initializer instanceof HasClassDefinitions) {
            foreach ($initializer->getClassDefinitions() as $concrete => $abstracts) {
                $this->container->bind($concrete, ...Arr::wrap($abstracts));
            }
        }

        if ($initializer instanceof HasFacades) {
            foreach ($initializer->getFacades() as $facade) {
                $facade->setContainer($this->container);
            }
        }

        if ($initializer instanceof Loadable) {
            $initializer->load();
        }
    }
}