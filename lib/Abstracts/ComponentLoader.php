<?php

namespace Phoenix\Loader\Abstracts;

use Phoenix\Loader\Interfaces\HasLoadCondition;
use Phoenix\Loader\Interfaces\Loadable;
use Phoenix\Loader\Traits\CanConditionallyLoad;

abstract class ComponentLoader implements Loadable, HasLoadCondition
{
    use CanConditionallyLoad;

    /**
     * @var Loadable[]
     */
    protected array $components = [];

    protected function loadComponent(): void
    {
        foreach ($this->components as $component) {
            $component->load();
        }
    }

    /** @inheritDoc */
    abstract public function shouldLoad(): bool;
}
