<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface TypeCheck
{
    public function __invoke(Request $request, array $checkers): Collection;
}
