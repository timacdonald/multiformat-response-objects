<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function app;
use function array_key_exists;
use function array_merge;
use Exception;
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
    public static function make(array $data): self
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
    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * @return static
     */
    public function withFallbackExtension(string $extension): self
    {
        $this->fallbackExtension = $extension;

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
