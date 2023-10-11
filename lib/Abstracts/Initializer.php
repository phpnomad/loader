<?php

namespace Phoenix\Loader\Abstracts;

use Phoenix\Loader\Interfaces\HasLoadCondition;
use Phoenix\Loader\Interfaces\Loadable;
use Phoenix\Loader\Traits\CanConditionallyLoad;

abstract class Initializer implements Loadable, HasLoadCondition
{
    use CanConditionallyLoad;
}
