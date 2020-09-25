<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use TiMacDonald\Multiformat\ResponseType;

interface TypeToCallback
{
    public function __invoke(ResponseType $responseType): callable;
}
