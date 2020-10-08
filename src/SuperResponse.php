<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function array_key_exists;

use function array_merge;
use function assert;

use BadMethodCallException;
use Closure;
use Exception;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Application;
use function is_callable;
use function method_exists;
use TiMacDonald\Multiformat\Contracts\ApiFallback;
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
    public static function make(array $data = [])
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

        return $this;
    }

    /**
     * @return static
     */
    public function withApiFallback(Closure $callback)
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

        $responseTypes = $typeCheck($request, $this->typeCheckers);
        assert($responseTypes instanceof ResponseTypes);

        $callback = $responseTypes->isKnown()
            ? $typeToCallback($responseTypes)
            : ($this->localFallback ?? $app->make(ApiFallback::class));
        assert(is_callable($callback));

        /**
         * @var mixed
         */
        $response = $app->call($callback($this), ['request' => $request]);

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
    protected function resolve(string $method)
    {
        return Application::getInstance()->call([$this, $method]);
    }

    /**
     * @return mixed
     */
    public function __get(string $attribute)
    {
        if (array_key_exists($attribute, $this->data)) {
            return $this->data[$attribute];
        }

        throw new Exception('Accessing undefined attribute '.static::class.'::'.$attribute);
    }

    /**
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        // this allows developers to intercept unknown type calls
        // in their response class and determine how they would like
        // to handle it. You can return a response, redirect, or throw
        // an exception.
        if (method_exists($this, 'handleUnknownType')) {
            return $this->handleUnknownType($method);
        }

        throw new BadMethodCallException('Method '.static::class, '::'.$method.' does not exist.');
    }
}
