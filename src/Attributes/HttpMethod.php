<?php

namespace Florddev\LaravelAutoRouting\Attributes;

use Attribute;

abstract class HttpMethod
{
    public array $options;

    public function __construct(...$options)
    {
        $this->options = $options;
    }
}
