<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Functions;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function method_exists;
use TiMacDonald\Multiformat\Contracts\TypesToCallback as TypesToCallbackContract;
use TiMacDonald\Multiformat\ResponseType;

class TypesToCallback implements TypesToCallbackContract
{
    public function __invoke(Collection $options): callable
    {
        $types = $options->map(static function (ResponseType $responseTypes): string {
            return $responseTypes
                ->options()
                ->map(static function (string $type): string {
                    // replace bad method chars with regex from: https://www.php.net/manual/en/functions.user-defined.php
                    return Str::ucfirst($type);
                })
                ->join('');
        });

        $method = "to{$types->join('')}Response";

        return static function (object $response) use ($method): ?callable {
            if (! method_exists($response, $method)) {
                return null;
            }

            return [$response, $method];
        };
    }
}
