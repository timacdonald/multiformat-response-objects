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
use TiMacDonald\Multiformat\Contracts\TypeCheck;
use TiMacDonald\Multiformat\Contracts\TypeToCallback;

trait SuperResponse
{
    /**
     * @var Closure|null
     */
    private $localFallback;

    /**
     * @var mixed[]
     */
    private $typeCheckers = [];

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
     * @return static
     */
    public function withTypeCheckers(array $typeCheckers)
    {
        $this->typeCheckers = array_merge($this->typeCheckers, $typeCheckers);
    }

    /**
     * @param mixed $callback
     *
     * @return static
     */
    public function withApiFallback($callback)
    {
        $this->localFallback = $callback;

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
        $typeToCallback = $app->make(TypeToCallback::class);
        assert(is_callable($typeToCallback));
        $typeCheck = $app->make(TypeCheck::class);
        assert(is_callable($typeCheck));

        $responseType = $typeCheck($request, $this->typeCheckers);
        assert($responseType instanceof ResponseType);

        $callback = $responseType->isKnown()
            ? $typeToCallback($responseType)
            : ($this->localFallback ?? $app->make(ApiFallback::class));

        $response = $app->call($callback($this), ['request' => $request]);

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
