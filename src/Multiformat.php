<?php

namespace TiMacDonald\Multiformat;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use TiMacDonald\Multiformat\Contracts\Extension;

trait Multiformat
{
    /**
     * @var ?string
     */
    protected $fallbackExtension;

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
        return (new static)->with($data);
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
    public function withFallbackExtension(string $extension)
    {
        $this->fallbackExtension = $extension;

        return $this;
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $extension = app(Extension::class)->parse(
            $request,
            $this->fallbackExtension ? new FallbackExtension($this->fallbackExtension) : null
        );

        $method = app(Method::class)->parse($this, $extension);

        return app()->call($method, ['request' => $request]);
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
