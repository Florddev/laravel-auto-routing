<?php

namespace Florddev\LaravelAutoRouting\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpGet
{
    public function __construct(public ?string $url = null, public ?string $name = null, public $middleware = null) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPost
{
    public function __construct(public ?string $url = null, public ?string $name = null, public $middleware = null) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPut
{
    public function __construct(public ?string $url = null, public ?string $name = null, public $middleware = null) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPatch
{
    public function __construct(public ?string $url = null, public ?string $name = null, public $middleware = null) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class HttpDelete
{
    public function __construct(public ?string $url = null, public ?string $name = null, public $middleware = null) {}
}
