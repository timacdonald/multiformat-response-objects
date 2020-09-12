<?php

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;

interface ExtensionGuesser
{
    public function guess(Request $request): ?string;
}
