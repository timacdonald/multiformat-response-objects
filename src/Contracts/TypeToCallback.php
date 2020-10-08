<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Closure;
use TiMacDonald\Multiformat\ResponseTypes;

interface TypeToCallback
{
    public function __invoke(ResponseTypes $responseTypes): Closure;
}
