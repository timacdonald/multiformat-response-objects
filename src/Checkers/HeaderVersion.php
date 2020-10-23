<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Checkers;

use Illuminate\Http\Request;
use function is_string;
use TiMacDonald\Multiformat\ResponseType;

class HeaderVersion
{
    use Concerns\DetectValidMethodStrings;

    public function __invoke(Request $request): ?ResponseType
    {
        $version = $request->header('Api-Version');

        if (! is_string($version)) {
            return null;
        }

        if (self::doesntContainAnyValidMethodCharacters($version)) {
            return null;
        }

        return new ResponseType("Version{$version}");
    }
}
