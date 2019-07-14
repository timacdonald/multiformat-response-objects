<?php

namespace TiMacDonald\MultiFormat;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UrlExtension
{
    public function parse(Request $request): ?string
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
}
