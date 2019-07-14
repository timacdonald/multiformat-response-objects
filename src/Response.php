<?php

namespace TiMacDonald\MultiFormat;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

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
        return app()->call([$this, $this->responseMethod($request)], [
            'request' => $request,
        ]);
    }

    public function withDefaultFormat(string $format): self
    {
        $this->defaultFormat = $format;

        return $this;
    }

    private function responseMethod(Request $request): string
    {
        return 'to'.Str::studly($this->contentType($request)).'Response';
    }

    private function contentType(Request $request): string
    {
        return $this->urlContentType($request)
            ?? $this->acceptHeaderType($request)
            ?? $this->defaultFormat;
    }

    private function acceptHeaderType(Request $request) : ?string
    {
        return (new MimeExtension($this->formatOverrides))->find(
            $request->getAcceptableContentTypes()
        ) ?? $request->format(null);
    }

    private function urlContentType(Request $request): ?string
    {
        return $this->extension($this->filename($request));
    }

    private function extension(string $filename): ?string
    {
        if (! Str::contains($filename, '.')) {
            return null;
        }

        return Arr::last(explode('.', $filename));
    }

    private function filename(Request $request): string
    {
        return Arr::last(explode('/', $request->path()));
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
