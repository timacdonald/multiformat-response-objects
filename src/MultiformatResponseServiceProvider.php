<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use TiMacDonald\Multiformat\Contracts\Extension as ExtensionContract;

class MultiformatResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomMimeTypes::class, static function (): CustomMimeTypes {
            return new CustomMimeTypes([]);
        });

        $this->app->bind(ApiFallbackExtension::class, static function (): ApiFallbackExtension {
            return new ApiFallbackExtension('html');
        });

        $this->app->bind(UrlExtension::class, static function (): UrlExtension {
            return new UrlExtension([]);
        });

        $this->app->bind(ExtensionContract::class, static function (Application $app): ExtensionContract {
            $urlExtension = $app->make(UrlExtension::class);
            $mimeExtension = $app->make(MimeExtension::class);
            $fallbackExtension = $app->make(ApiFallbackExtension::class);

            assert($urlExtension instanceof UrlExtension);
            assert($mimeExtension instanceof MimeExtension);
            assert($fallbackExtension instanceof ApiFallbackExtension);

            return new Extension([
                $urlExtension,
                $mimeExtension,
            ]);
        });
    }
}
