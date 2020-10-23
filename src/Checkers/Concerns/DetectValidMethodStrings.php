<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Checkers\Concerns;

use function preg_match;

trait DetectValidMethodStrings
{
    private static function doesntContainAnyValidMethodCharacters(string $characters): bool
    {
        return ! self::containsSomeValidMethodCharacters($characters);
    }

    private static function containsSomeValidMethodCharacters(string $characters): bool
    {
        return preg_match('/[a-zA-Z0-9_\\x80-\\xff]/', $characters) === 1;
    }
}
