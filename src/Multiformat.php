<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function array_key_exists;
use function array_merge;

use function assert;
use Closure;
use Exception;
use Illuminate\Contracts\Support\Responsable;

use Illuminate\Foundation\Application;
use function is_callable;
use function is_string;

trait Multiformat
{
    /**
     * @var Closure|string|null
     */
    private $apiFallback;

    /**
     * @var mixed[]
     */
    private $data = [];

    /**
     * @return static
     */
    public static function make(array $data)
    {
        /**
         * @psalm-suppress UnsafeInstantiation
         * @phpstan-ignore-next-line
         */
        return (new static())->with($data);
    }

    /**
     * @return static
     */
    public static function new(...$params)
    {
        /**
         * @psalm-suppress UnsafeInstantiation
         * @psalm-suppress TooManyArguments
         * @phpstan-ignore-next-line
         */
        return new static(...$params);
    }

    /**
     * @return static
     */
    public function with(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * @param Closure|string $type
     *
     * @return static
     */
    public function withApiFallback($type)
    {
        $this->apiFallback = $type;

        return $this;
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $app = Application::getInstance();

        $method = $app->make(Method::class);
        assert($method instanceof Method);

        $callback = $method->callback($request, $this) ?? $this->apiFallback ?? $app->make(ApiFallback::class);

        if (! is_callable($callback)) {
            assert(is_string($callback));

            $callback = function () use ($method, $callback): callable {
                return [$this, $method->name($this, $callback)];
            };
        }

        $response = $app->call($callback($request, $this), ['request' => $request]);

        while ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        throw new Exception('Accessing undefined attribute '.static::class.'::'.$key);
    }
}
