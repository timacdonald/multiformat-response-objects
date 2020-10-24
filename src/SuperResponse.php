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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use function is_callable;
use function method_exists;
use TiMacDonald\Multiformat\Contracts\FallbackResponse;
use TiMacDonald\Multiformat\Contracts\TypeCheck;
use TiMacDonald\Multiformat\Contracts\TypesToCallback;

trait SuperResponse
{
    /**
     * @var callable|mixed
     */
    private $fallbackResponse;

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
    public static function make(array $data = [])
    {
        /**
         * @psalm-suppress UnsafeInstantiation
         * @phpstan-ignore-next-line
         */
        return (new static())->with($data);
    }

    /**
     * @param mixed ...$params
     *
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

        return $this;
    }

    /**
     * @return static
     */
    public function withFallbackResponse(callable $callback)
    {
        $this->fallbackResponse = static function (Request $request, object $response) use ($callback): Closure {
            return static function () use ($request, $response, $callback) {
                return $callback($request, $response);
            };
        };

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

        $typeToCallback = $app->make(TypesToCallback::class);
        assert(is_callable($typeToCallback));

        $typeCheck = $app->make(TypeCheck::class);
        assert(is_callable($typeCheck));

        $options = $typeCheck($request, $this->typeCheckers);
        assert($options instanceof Collection);

        $callback = null;

        if ($options->isNotEmpty()) {
            $callback = $typeToCallback($options)($this);
        }

        if ($callback === null) {
            $callback = $this->fallbackResponse ?? $app->make(FallbackResponse::class);
        }

        if (! is_callable($callback)) {
            $callback = static function () use ($callback) {
                return $callback;
            };
        }

        $responseMethod = $callback($request, $this);

        if (! is_callable($responseMethod)) {
            $responseMethod = static function () use ($responseMethod) {
                return $responseMethod;
            };
        }

        $response = $app->call($responseMethod, ['request' => $request]);

        // It can be nice to not return responses, but instead return data
        // from your toXXXXResponse methods, for example you may want
        // to return an array from a versioned toVersion_1_2_2_Response
        // method and then wrap that array in a response at the very end
        // with `return response()->json($payload);`. This is nice because
        // it means you aren't passing around responses.
        if (method_exists($this, 'prepareResponse')) {
            $response = $this->prepareResponse($response);
        }

        // This allows you to return nested super responses, but also return
        // other Responseables, such as Laravel's JSON Resources. We iterate
        // over all nested Responseables to resolve the final response.
        while ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        // As you can nest responsables with the super response, this allows
        // you to intercept the *final* response, which is after all the nesting
        // has been resolved.
        if (method_exists($this, 'prepareFinalResponse')) {
            $response = $this->prepareFinalResponse($response);
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function __get(string $attribute)
    {
        if (! array_key_exists($attribute, $this->data)) {
            throw new Exception('Undefined property: '.static::class.'::'.$attribute);
        }

        return $this->data[$attribute];
    }
}
