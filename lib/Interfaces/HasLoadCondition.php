<?php

namespace Phoenix\Loader\Interfaces;
interface HasLoadCondition
{
    /**
     * Returns true if the item should load.
     *
     * @return bool
     */
    public function shouldLoad(): bool;
}