<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Exception;
use function get_class;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use function is_callable;
use function method_exists;
use TiMacDonald\Multiformat\Contracts\Extension;

class Method
{
    /**
     * @var Extension
     */
    private $extension;

    public function __construct(Extension $extension)
    {
        $this->extension = $extension;
    }

    public function parse(Request $request, object $response, ApiFallbackExtension $fallbackExtension): callable
    {
        $extension = $this->extension->parse($request) ?? $fallbackExtension->value();

        return self::method($response, self::name($response, $extension));
    }

    private static function method(object $response, string $name): callable
    {
        $method = [$response, $name];

        assert(is_callable($method));

        return $method;
    }

    private static function name(object $response, string $extension): string
    {
        $name = 'to'.Str::studly($extension).'Response';

        if (! method_exists($response, $name)) {
            throw new Exception('Method '.get_class($response).'::'.$name.'() does not exist');
        }

        return $name;
    }
}
