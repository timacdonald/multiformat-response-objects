<?php

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;
use TiMacDonald\Multiformat\FallbackExtension;

interface Extension
{
    public function parse(Request $request, ?FallbackExtension $fallbackExtension): string;
}
