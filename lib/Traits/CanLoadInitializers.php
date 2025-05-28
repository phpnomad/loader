<?php

namespace PHPNomad\Loader\Traits;

use PHPNomad\Console\Interfaces\ConsoleStrategy;
use PHPNomad\Console\Interfaces\HasCommands;
use PHPNomad\Di\Exceptions\DiException;
use PHPNomad\Di\Interfaces\CanSetContainer;
use PHPNomad\Di\Interfaces\InstanceProvider;
use PHPNomad\Events\Interfaces\Event;
use PHPNomad\Events\Interfaces\EventStrategy;
use PHPNomad\Events\Interfaces\HasEventBindings;
use PHPNomad\Events\Interfaces\HasListeners;
use PHPNomad\Facade\Interfaces\HasFacades;
use PHPNomad\Events\Interfaces\ActionBindingStrategy;
use PHPNomad\Loader\Exceptions\LoaderException;
use PHPNomad\Loader\Interfaces\HasClassDefinitions;
use PHPNomad\Loader\Interfaces\HasLoadCondition;
use PHPNomad\Loader\Interfaces\Loadable;
use PHPNomad\Mutator\Interfaces\HasMutations;
use PHPNomad\Mutator\Interfaces\MutationStrategy;
use PHPNomad\Rest\Interfaces\HasControllers;
use PHPNomad\Rest\Interfaces\RestStrategy;
use PHPNomad\Tasks\Interfaces\HasTaskHandlers;
use PHPNomad\Tasks\Interfaces\Task;
use PHPNomad\Tasks\Interfaces\TaskStrategy;
use PHPNomad\Update\Events\UpgradeRoutinesRequested;
use PHPNomad\Update\Interfaces\HasUpdates;
use PHPNomad\Utils\Helpers\Arr;

trait CanLoadInitializers
{
    protected InstanceProvider $container;

    /**
     * @var array[HasClassDefinitions|Loadable|HasLoadCondition|HasFacades|HasListeners|HasMutations|HasEventBindings|HasTaskHandlers]
     */
    protected array $initializers = [];

    /**
     * @return void
     * @throws LoaderException
     */
    protected function loadInitializers()
    {
        foreach ($this->initializers as $initializer) {
            $this->loadInitializer($initializer);
        }
    }

    protected function loadInitializer($initializer): void
    {
        try {
            if ($initializer instanceof CanSetContainer) {
                $initializer->setContainer($this->container);
            }

            if ($initializer instanceof HasLoadCondition && !$initializer->shouldLoad()) {
                return;
            }

            if ($initializer instanceof HasClassDefinitions) {
                foreach ($initializer->getClassDefinitions() as $concrete => $abstracts) {
                    $this->container->bindSingleton($concrete, ...Arr::wrap($abstracts));
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
                    foreach (Arr::wrap($actions) as $action) {
                        if (is_array($action)) {
                            $strategy->bindAction($binding, $action['action'], $action['transformer']);
                        } else {
                            $strategy->bindAction($binding, $action);
                        }
                    }
                }
            }

            if ($initializer instanceof HasListeners) {
                $events = $this->container->get(EventStrategy::class);

                foreach ($initializer->getListeners() as $event => $handlers) {
                    foreach (Arr::wrap($handlers) as $handler) {
                        $events->attach(
                            $event,
                            fn(Event $event) => $this->container->get($handler)->handle($event)
                        );
                    }
                }
            }

            if ($initializer instanceof HasTaskHandlers) {
                $strategy = $this->container->get(TaskStrategy::class);

                foreach ($initializer->getTaskHandlers() as $taskClass => $handlers) {
                    foreach (Arr::wrap($handlers) as $handlerClass) {
                        $strategy->attach($taskClass, fn(Task $task) =>
                            $this->container->get($handlerClass)->handle($task)
                        );
                    }
                }
            }

            if ($initializer instanceof HasUpdates) {
                $events = $this->container->get(EventStrategy::class);

                $events->attach(
                    UpgradeRoutinesRequested::class,
                    fn(UpgradeRoutinesRequested $event) => $event->maybeRegisterRoutines(...$initializer->getRoutines())
                );
            }

            if ($initializer instanceof HasControllers) {
                $strategy = $this->container->get(RestStrategy::class);
                foreach ($initializer->getControllers() as $controller) {
                    $strategy->registerRoute(fn() => $this->container->get($controller));
                }
            }

            if ($initializer instanceof HasCommands) {
                $strategy = $this->container->get(ConsoleStrategy::class);
                foreach ($initializer->getCommands() as $command) {
                    $strategy->registerCommand(fn() => $this->container->get($command));
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
