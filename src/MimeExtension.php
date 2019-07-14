<?php

namespace TiMacDonald\MultiFormat;

use Illuminate\Http\Request;
use Symfony\Component\Mime\MimeTypes;

class MimeExtension
{
    /**
     * @var array
     */
    private $overrides;

    /**
     * @var \Symfony\Component\Mime\MimeTypes
     */
    private $mimeTypes;

    public function __construct(array $overrides = [])
    {
        $this->overrides = $overrides;

        $this->mimeTypes = new MimeTypes;
    }

    public function parse(Request $request): ?string
    {
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            $extension = $this->getOverride($contentType) ?? $this->getExtension($contentType);

            if ($extension !== null) {
                return $extension;
            }
        }

        return $request->format(null);
    }

    private function getExtension(string $contentType): ?string
    {
        return $this->mimeTypes->getExtensions($contentType)[0] ?? null;
    }

    private function getOverride(string $contentType): ?string
    {
        return $this->overrides[$contentType] ?? null;
    }
}
