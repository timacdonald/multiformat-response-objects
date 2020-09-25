<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;
use TiMacDonald\Multiformat\ResponseType;

interface TypeCheck
{
    public function __invoke(Request $request, array $checkers): ResponseType;
}
