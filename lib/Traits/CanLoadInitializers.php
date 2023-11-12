<?php

namespace PHPNomad\Loader\Traits;

use PHPNomad\Di\Container;
use PHPNomad\Di\Exceptions\DiException;
use PHPNomad\Di\Interfaces\CanSetContainer;
use PHPNomad\Events\Interfaces\CanListen;
use PHPNomad\Events\Interfaces\HasEventBindings;
use PHPNomad\Events\Interfaces\HasListeners;
use PHPNomad\Facade\Interfaces\HasFacades;
use PHPNomad\Integrations\WordPress\Strategies\ActionBindingStrategy;
use PHPNomad\Loader\Exceptions\LoaderException;
use PHPNomad\Loader\Interfaces\HasClassDefinitions;
use PHPNomad\Loader\Interfaces\HasLoadCondition;
use PHPNomad\Loader\Interfaces\Loadable;
use PHPNomad\Mutator\Interfaces\HasMutations;
use PHPNomad\Mutator\Interfaces\MutationStrategy;
use PHPNomad\Rest\Interfaces\Controller;
use PHPNomad\Rest\Interfaces\HasControllers;
use PHPNomad\Rest\Interfaces\RestStrategy;
use PHPNomad\Utils\Helpers\Arr;
use PHPNomad\Utils\Helpers\Str;

trait CanLoadInitializers
{
    protected Container $container;

    /**
     * @var array[HasClassDefinitions|Loadable|HasLoadCondition|HasFacades|HasListeners|HasMutations|HasEventBindings]
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
     * @param HasClassDefinitions|Loadable|HasLoadCondition|HasFacades|HasListeners|HasMutations|HasEventBindings $initializer
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

            if ($initializer instanceof HasMutations) {
                foreach ($initializer->getMutations() as $mutation => $actions) {
                    $strategy = $this->container->get(MutationStrategy::class);
                    foreach ($actions as $action) {
                        $strategy->attach(fn() => $this->container->get($mutation), $action);
                    }
                }
            }

            if ($initializer instanceof HasEventBindings) {
                foreach ($initializer->getEventBindings() as $binding => $actions) {
                    $strategy = $this->container->get(ActionBindingStrategy::class);
                    foreach ($actions as $action) {
                        $strategy->bindAction($binding, $action);
                    }
                }
            }

            if($initializer instanceof HasListeners){
                foreach($initializer->getListeners() as $listener){
                    /** @var CanListen $instance */
                    $instance = $this->container->get($listener);

                    $instance->listen();
                }
            }

            if($initializer instanceof HasControllers){
                $strategy = $this->container->get(RestStrategy::class);
                foreach($initializer->getControllers() as $controller){
                    $strategy->registerRoute(fn() => $this->container->get($controller));
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
        } catch (DiException $e) {
            throw new LoaderException('Failed to load ' . get_class($initializer), 500, $e);
        }
    }
}