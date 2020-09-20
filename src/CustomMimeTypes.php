<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

class CustomMimeTypes
{
    /**
     * @var string[]
     */
    private $mimeTypes;

    /**
     * @param string[] $mimeTypes
     */
    public function __construct(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function find(string $mimeType): ?string
    {
        return $this->mimeTypes[$mimeType] ?? null;
    }
}
