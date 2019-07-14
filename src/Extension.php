<?php

namespace TiMacDonald\MultiFormat;

use Illuminate\Http\Request;

class Extension
{
    /**
     * @var array
     */
    private $formatOverrides;

    public function __construct(array $formatOverrides = [])
    {
        $this->formatOverrides = $formatOverrides;
    }

    public function parse(Request $request, string $defaultFormat = 'html'): string
    {
        return $this->urlExtension($request)
            ?? $this->acceptHeaderExtension($request)
            ?? $defaultFormat;
    }

    private function acceptHeaderExtension(Request $request) : ?string
    {
        return (new MimeExtension($this->formatOverrides))->parse($request);
    }

    private function urlExtension(Request $request): ?string
    {
        return (new UrlExtension)->parse($request);
    }
}
