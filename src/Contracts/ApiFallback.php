<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

interface ApiFallback
{
    public function __invoke(object $response): callable;
}
