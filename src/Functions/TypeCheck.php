<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Functions;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function is_callable;
use TiMacDonald\Multiformat\Contracts\TypeCheck as TypeCheckContract;
use TiMacDonald\Multiformat\ResponseType;

class TypeCheck implements TypeCheckContract
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, array $checkers): Collection
    {
        return Collection::make($checkers)
            ->map(
                 /** @param callable|string $checker */
                 function ($checker): callable {
                     if (is_callable($checker)) {
                         return $checker;
                     }

                     /** @var callable */
                     return $this->container->make($checker);
                 }
            )->map(static function (callable $checker) use ($request): ResponseType {
                /** @var ResponseType */
                return $checker($request);
            });
    }
}
