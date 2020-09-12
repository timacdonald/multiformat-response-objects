<?php

namespace TiMacDonald\Multiformat;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TiMacDonald\Multiformat\Contracts\ExtensionGuesser;

class UrlExtension implements ExtensionGuesser
{
    /**
     * @var string[]
     */
    private $formatOverrides;

    /**
     * @param string[] $formatOverrides
     */
    public function __construct(array $formatOverrides = [])
    {
        $this->formatOverrides = $formatOverrides;
    }

    public function guess(Request $request): ?string
    {
        $filename =  Arr::last(explode('/', $request->path()));

        assert(is_string($filename));

        if (! Str::contains($filename, '.')) {
            return null;
        }

        $extension = Arr::last(explode('.', $filename));

        assert(is_string($extension));

        return $extension;
    }
}
