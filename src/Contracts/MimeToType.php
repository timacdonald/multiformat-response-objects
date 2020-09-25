<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

interface MimeToType
{
    public function __invoke(string $mime): ?string;
}
