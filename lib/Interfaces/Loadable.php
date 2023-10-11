<?php

namespace Phoenix\Loader\Interfaces;

interface Loadable
{
    /**
     * Loads this thing.
     * @return void
     */
    public function load(): void;
}