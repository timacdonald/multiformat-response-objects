<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Checkers;

use Illuminate\Http\Request;
use function is_numeric;

use function is_string;
use function str_replace;
use TiMacDonald\Multiformat\ResponseTypes;

class QueryVersion
{
    public function __invoke(Request $request): ?ResponseTypes
    {
        $version = $request->query('v');

        if (! is_string($version)) {
            return null;
        }

        if (! is_numeric(str_replace('.', '', $version))) {
            return null;
        }

        return new ResponseTypes(['version_'.str_replace('.', '_', $version).'_']);
    }
}
