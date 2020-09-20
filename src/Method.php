<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;

use Closure;
use Exception;
use function get_class;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use function is_callable;
use function method_exists;

class Method
{
    /**
     * @var Type
     */
    private $type;

    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    public function callback(Request $request, object $response): ?Closure
    {
        $type = $this->type->check($request);

        if ($type === null) {
            return null;
        }

        return function (Request $request, object $response) use ($type): callable {
            $method = [$response, $this->name($response, $type)];

            assert(is_callable($method));

            return $method;
        };
    }

    public function name(object $response, string $type): string
    {
        $name = 'to'.Str::studly($type).'Response';

        if (! method_exists($response, $name)) {
            throw new Exception('Method '.get_class($response).'::'.$name.'() does not exist');
        }

        return $name;
    }
}
