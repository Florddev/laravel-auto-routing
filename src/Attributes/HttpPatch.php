<?php

namespace Florddev\LaravelAutoRouting\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPatch extends HttpMethod {}