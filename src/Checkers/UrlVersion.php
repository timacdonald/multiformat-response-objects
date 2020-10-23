<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Checkers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use function is_string;
use TiMacDonald\Multiformat\ResponseType;

class UrlVersion
{
    use Concerns\DetectValidMethodStrings;

    public function __invoke(Request $request): ?ResponseType
    {
        $version = $request->route('version');

        if (! is_string($version)) {
            return null;
        }

        if (self::doesntContainAnyValidMethodCharacters($version)) {
            return null;
        }

        $version = Str::after($version, 'v');

        return new ResponseType("Version{$version}");
    }
}
