<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function app;
use function array_key_exists;
use function array_merge;
use Exception;

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
     *
     * @return mixed
     */
    public function toResponse($request)
    {
        $method = app(Method::class)->parse(
            $request,
            $this,
            $this->apiFallbackExtension ?? app(ApiFallbackExtension::class)
        );

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
