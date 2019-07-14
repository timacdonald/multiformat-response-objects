<?php

namespace TiMacDonald\MultiFormat;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Responsable;

class Response implements Responsable
{
    /**
     * @var string
     */
    protected $defaultFormat = 'html';

    /**
     * @var array
     */
    protected $formatOverrides = [];

    /**
     * @var array
     */
    private $data = [];

    public static function make(array $data = []): self
    {
        return (new static)->with($data);
    }

    public function with($data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function withDefaultFormat(string $format): self
    {
        $this->defaultFormat = $format;

        return $this;
    }

    public function withFormatOverrides(array $formatOverrides): self
    {
        $this->formatOverrides = array_merge($this->formatOverrides, $formatOverrides);

        return $this;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return Container::getInstance()->call([$this, $this->responseMethod($request)], [
            'request' => $request,
        ]);
    }

    private function responseMethod(Request $request): string
    {
        return 'to'.Str::studly($this->extension($request)).'Response';
    }

    private function extension(Request $request): string
    {
        return (new Extension($this->formatOverrides))->parse($request, $this->defaultFormat);
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
