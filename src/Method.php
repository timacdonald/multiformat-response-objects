<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Exception;
use function get_class;
use Illuminate\Support\Str;
use function is_callable;
use function method_exists;

class Method
{
    public function parse(object $response, string $extension): callable
    {
        return $this->method($response, $this->name($response, $extension));
    }

    private function method(object $response, string $name): callable
    {
        $method = [$response, $name];

        assert(is_callable($method));

        return $method;
    }

    private function name(object $response, string $extension): string
    {
        $name = 'to'.Str::studly($extension).'Response';

        if (! method_exists($response, $name)) {
            throw new Exception('Method '.get_class($response).'::'.$name.'() does not exist');
        }

        return $name;
    }
}
