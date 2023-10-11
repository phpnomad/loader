<?php

namespace Phoenix\Loader\Traits;

use Phoenix\Loader\Interfaces\HasLoadCondition;
use Phoenix\Loader\Interfaces\Loadable;

trait CanLoadComponents
{
    /**
     * @var Loadable[]
     */
    protected array $components = [];

    public function loadComponents(): void
    {
        foreach ($this->components as $component) {
            $this->handleLoadingItem($component);
        }
    }

    protected function handleLoadingItem($component)
    {
        // Bail early if this has a load condition preventing it from loading.
        if ($component instanceof HasLoadCondition && !$component->shouldLoad()) {
            return;
        }

        if ($component instanceof Loadable) {
            $component->load();
        }
    }
}