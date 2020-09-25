<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Checkers;

use function assert;

use function explode;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use function is_string;

use TiMacDonald\Multiformat\ResponseType;

class UrlType
{
    public function __invoke(Request $request): ?ResponseType
    {
        $filename = Arr::last(explode('/', $request->path()));

        assert(is_string($filename));

        if (! Str::contains($filename, '.')) {
            return null;
        }

        $type = Arr::last(explode('.', $filename));

        assert(is_string($type));

        return new ResponseType($type);
    }
}
