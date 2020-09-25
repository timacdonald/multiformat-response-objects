<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Closure;
use Exception;

use function get_class;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use function is_callable;
use function method_exists;
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

        $this->app->bind(ApiFallback::class, static function (Application $app): Closure {
            $method = $app->make(Method::class);

            assert($method instanceof Method);

            return $method->callback(new ResponseType('html'));
        });

        $this->app->bind(TypeCheck::class, static function (): Closure {
            return static function (Request $request, array $typeCheckers): ResponseType {
                $responseType = Collection::make($typeCheckers)
                    ->reduce(static function (ResponseType $carry, callable $guesser) use ($request): ResponseType {
                        return $carry->add((string) $guesser($request));
                    }, ResponseType::makeUnknown());

                assert($responseType instanceof ResponseType);

                return $responseType;
            };
        });

        $this->app->bind(TypeToCallback::class, static function (): Closure {
            return static function (ResponseType $responseType): Closure {
                $name = "to{$responseType->value()}Response";

                return static function (object $response) use ($name): callable {
                    if (! method_exists($response, $name)) {
                        throw new Exception('Method '.get_class($response).'::'.$name.'() does not exist');
                    }

                    $method = [$response, $name];

                    assert(is_callable($method));

                    return $method;
                };
            };
        });
    }
}
