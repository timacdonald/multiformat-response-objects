<?php

namespace TiMacDonald\MultiFormat;

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

    public function find(array $contentTypes): ?string
    {
        foreach ($contentTypes as $contentType) {
            $extension = $this->getOverride($contentType) ?? $this->getExtension($contentType);

            if ($extension !== null) {
                return $extension;
            }
        }

        return null;
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
