<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Http\Request;

interface ApiFallback
{
    public function __invoke(Request $request, object $response): callable;
}
