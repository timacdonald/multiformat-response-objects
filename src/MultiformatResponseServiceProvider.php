<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use function is_callable;

class MultiformatResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomMimeTypes::class, static function (): CustomMimeTypes {
            return new CustomMimeTypes([]);
        });

        $this->app->bind(ApiFallback::class, static function (Application $app): Closure {
            return static function (Request $request, object $response) use ($app): callable {
                $method = $app->make(Method::class);

                assert($method instanceof Method);

                return [$response, $method->name($response, 'html')];
            };
        });

        $this->app->bind(Type::class, static function (Application $app): Type {
            $urlExtension = $app->make(UrlExtension::class);
            assert(is_callable($urlExtension));

            $mimeExtension = $app->make(MimeExtension::class);
            assert(is_callable($mimeExtension));

            return new Type([$urlExtension, $mimeExtension]);
        });
    }
}
