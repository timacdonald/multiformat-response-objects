<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TiMacDonald\Multiformat\Contracts\Extension as ExtensionContract;

class MultiformatResponseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(FallbackExtension::class, static function (): FallbackExtension {
            return new FallbackExtension('html');
        });

        $this->app->bind(ExtensionContract::class, static function (Application $app): ExtensionContract {
            $urlExtension = $app->make(UrlExtension::class);
            $mimeExtension = $app->make(MimeExtension::class);
            $fallbackExtension = $app->make(FallbackExtension::class);

            assert($urlExtension instanceof UrlExtension);
            assert($mimeExtension instanceof MimeExtension);
            assert($fallbackExtension instanceof FallbackExtension);

            return new Extension([
                $urlExtension,
                $mimeExtension,
            ], $fallbackExtension);
        });
    }

    public function provides()
    {
        return [
            FallbackExtension::class,
            ExtensionContract::class,
        ];
    }
}
