<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Closure;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

use function is_callable;
use Symfony\Component\Mime\MimeTypes;
use TiMacDonald\Multiformat\Contracts\ApiFallback;
use TiMacDonald\Multiformat\Contracts\MimeToType;
use TiMacDonald\Multiformat\Contracts\TypeCheck;
use TiMacDonald\Multiformat\Contracts\TypeToCallback;

class SuperResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MimeToType::class, static function (): Closure {
            $mimeTypes = new MimeTypes();

            return static function (string $mimeType) use ($mimeTypes): ?string {
                return $mimeTypes->getExtensions($mimeType)[0] ?? null;
            };
        });

        $this->app->bind(ApiFallback::class, static function (Application $app): callable {
            $typeToCallback = $app->make(TypeToCallback::class);

            assert(is_callable($typeToCallback));

            $callback = $typeToCallback(new ResponseTypes(['fallback']));

            assert(is_callable($callback));

            return $callback;
        });

        $this->app->bind(TypeCheck::class, static function (Application $app): Closure {
            return static function (Request $request, array $typeCheckers) use ($app): ResponseTypes {
                $responseTypes = Collection::make($typeCheckers)
                    ->map(
                        /**
                         * @param callable|string $typeChecker
                         */
                        static function ($typeChecker) use ($app): callable {
                            if (is_callable($typeChecker)) {
                                return $typeChecker;
                            }

                            $typeChecker = $app->make($typeChecker);

                            assert(is_callable($typeChecker));

                            return $typeChecker;
                        }
                    )
                    ->reduce(static function (ResponseTypes $carry, callable $guesser) use ($request): ResponseTypes {
                        return $carry->add($guesser($request));
                    }, ResponseTypes::makeUnknown());

                assert($responseTypes instanceof ResponseTypes);

                return $responseTypes;
            };
        });

        $this->app->bind(TypeToCallback::class, static function (): Closure {
            return static function (ResponseTypes $responseTypes): Closure {
                $name = $responseTypes->value()
                    ->map(static function (string $type): string {
                        return Str::ucfirst($type);
                    })
                    ->join('');

                return static function (object $response) use ($name): array {
                    return [$response, "to{$name}Response"];
                };
            };
        });
    }
}
