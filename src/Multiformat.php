<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function array_key_exists;
use function array_merge;
use function assert;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Application;

trait Multiformat
{
    /**
     * @var ?\TiMacDonald\Multiformat\ApiFallbackExtension
     */
    private $apiFallbackExtension;

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
    public function withApiFallbackExtension(string $extension)
    {
        $this->apiFallbackExtension = new ApiFallbackExtension($extension);

        return $this;
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     *
     * @param \Illuminate\Http\Request $request
     */
    public function toResponse($request): \Symfony\Component\HttpFoundation\Response
    {
        $app = Application::getInstance();

        $method = $app->make(Method::class);
        assert($method instanceof Method);

        $fallback = $this->apiFallbackExtension ?? $app->make(ApiFallbackExtension::class);
        assert($fallback instanceof ApiFallbackExtension);

        $callable = $method->parse($request, $this, $fallback);

        $response = $app->call($callable, ['request' => $request]);

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
