<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Support\ServiceProvider;
use TiMacDonald\Multiformat\Contracts\FallbackResponse as FallbackResponseContract;
use TiMacDonald\Multiformat\Contracts\MimeToType as MimeToTypeContract;
use TiMacDonald\Multiformat\Contracts\TypeCheck as TypeCheckContract;
use TiMacDonald\Multiformat\Contracts\TypesToCallback as TypesToCallbackContract;
use TiMacDonald\Multiformat\Functions\FallbackResponse;
use TiMacDonald\Multiformat\Functions\MimeToType;
use TiMacDonald\Multiformat\Functions\TypeCheck;
use TiMacDonald\Multiformat\Functions\TypesToCallback;

class SuperResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TypeCheckContract::class, TypeCheck::class);
        $this->app->bind(TypesToCallbackContract::class, TypesToCallback::class);
        $this->app->bind(FallbackResponseContract::class, FallbackResponse::class);
        $this->app->bind(MimeToTypeContract::class, MimeToType::class);
    }
}
