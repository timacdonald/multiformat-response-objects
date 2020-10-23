<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Support\Collection;

interface TypesToCallback
{
    public function __invoke(Collection $options): callable;
}
