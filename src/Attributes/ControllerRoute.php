<?php

namespace Florddev\LaravelAutoRouting\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ControllerRoute
{
    public array $options;

    public function __construct(...$options)
    {
        $this->options = $options;
    }
}