<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Functions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use function method_exists;
use TiMacDonald\Multiformat\Contracts\FallbackResponse as FallbackResponseContract;

class FallbackResponse implements FallbackResponseContract
{
    /**
     * @return mixed
     */
    public function __invoke(Request $request, object $response)
    {
        if (method_exists($response, 'unsupportedResponse')) {
            return $response->unsupportedResponse($request);
        }

        return new Response(null, 406);
    }
}
