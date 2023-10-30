<?php

namespace PHPNomad\Loader\Traits;

use PHPNomad\Di\Container;
use PHPNomad\Di\Exceptions\DiException;
use PHPNomad\Di\Interfaces\CanSetContainer;
use PHPNomad\Events\Interfaces\CanListen;
use PHPNomad\Events\Interfaces\HasListeners;
use PHPNomad\Facade\Interfaces\HasFacades;
use PHPNomad\Loader\Exceptions\LoaderException;
use PHPNomad\Loader\Interfaces\HasClassDefinitions;
use PHPNomad\Loader\Interfaces\HasLoadCondition;
use PHPNomad\Loader\Interfaces\Loadable;
use PHPNomad\Utils\Helpers\Arr;

trait CanLoadInitializers
{
    protected Container $container;

    /**
     * @var array[HasClassDefinitions|Loadable|HasLoadCondition|HasFacades]
     */
    protected array $initializers = [];

    /**
     * @throws LoaderException
     */
    protected function loadInitializers()
    {
        foreach ($this->initializers as $initializer) {
            $this->loadInitializer($initializer);
        }
    }

    /**
     * @param HasClassDefinitions|Loadable|HasLoadCondition|HasFacades $initializer
     * @return void
     * @throws LoaderException
     */
    protected function loadInitializer($initializer): void
    {
        try {
            if ($initializer instanceof CanSetContainer) {
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

            if ($initializer instanceof HasListeners) {
                foreach ($initializer->getListeners() as $listener) {
                    $this->container->get($listener)->listen();
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
        }catch(DiException $e){
            throw new LoaderException('Failed to load ' . get_class($initializer), 500, $e);
        }
    }
}