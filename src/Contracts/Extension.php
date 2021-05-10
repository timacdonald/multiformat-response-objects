<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;

interface Extension
{
    public function parse(Request $request): ?string;
}
