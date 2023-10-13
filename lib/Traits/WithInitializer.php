<?php

namespace Phoenix\Loader\Traits;
use Phoenix\Core\Bootstrap\Interfaces\Initializer;

trait WithInitializer
{
    protected Initializer $initializer;

    /**
     * Gets the initializer.
     *
     * @return Initializer
     */
    public function getInitializer(): Initializer
    {
        return $this->initializer;
    }
}