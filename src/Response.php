<?php

namespace TiMacDonald\MultiFormat;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Response implements Responsable
{
    /**
     * @var string
     */
    protected $defaultFormat = 'html';

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
        if ($format = $this->urlContentType($request->path())) {
            return $format;
        }

        $format = $request->format();

        if ($format !== 'html') {
            return $format;
        }

        return $this->defaultFormat;
    }

    private function urlContentType(string $path): ?string
    {
        return $this->extension($this->filename($path));
    }

    private function extension(string $filename): ?string
    {
        if (! Str::contains($filename, '.')) {
            return null;
        }

        return Arr::last(explode('.', $filename));
    }

    private function filename(string $path): string
    {
        return Arr::last(explode('/', $path));
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
