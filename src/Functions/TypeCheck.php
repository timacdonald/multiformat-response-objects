<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Functions;

use function app;
use function assert;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function is_callable;
use TiMacDonald\Multiformat\Contracts\TypeCheck as TypeCheckContract;
use TiMacDonald\Multiformat\ResponseType;

class TypeCheck implements TypeCheckContract
{
    public function __invoke(Request $request, array $checkers): Collection
    {
        return Collection::make($checkers)
            ->map(
                /** @param callable|string $checker */
                static function ($checker): callable {
                    if (is_callable($checker)) {
                        return $checker;
                    }

                    $checker = app()->make($checker);

                    assert(is_callable($checker));

                    return $checker;
                }
            )->map(static function (callable $checker) use ($request): ResponseType {
                $responseTypes = $checker($request);

                assert($responseTypes instanceof ResponseType);

                return $responseTypes;
            });
    }
}
