<?php

namespace PHPNomad\Events\Interfaces;

interface HasListeners
{
    /**
     * @return class-string<CanListen>[]
     */
    public function getListeners(): array;
}