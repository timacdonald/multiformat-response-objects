<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;

interface ExtensionGuesser
{
    public function guess(Request $request): ?string;
}
