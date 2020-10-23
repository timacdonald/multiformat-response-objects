<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;

interface FallbackResponse
{
    public function __invoke(Request $request, object $response): callable;
}
