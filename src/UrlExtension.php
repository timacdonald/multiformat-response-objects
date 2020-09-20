<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use function explode;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use function is_string;

class UrlExtension
{
    public function __invoke(Request $request): ?string
    {
        $filename = Arr::last(explode('/', $request->path()));

        assert(is_string($filename));

        if (! Str::contains($filename, '.')) {
            return null;
        }

        $extension = Arr::last(explode('.', $filename));

        assert(is_string($extension));

        return $extension;
    }
}
