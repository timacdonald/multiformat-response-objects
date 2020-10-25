<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Contracts;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface FallbackResponse
{
    /**
     * @return Closure|mixed|Response
     */
    public function __invoke(Request $request, object $response);
}
