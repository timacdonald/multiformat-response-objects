<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Functions;

use Symfony\Component\Mime\MimeTypes;
use TiMacDonald\Multiformat\Contracts\MimeToType as MimeToTypeContract;

class MimeToType implements MimeToTypeContract
{
    /**
     * @var MimeTypes
     */
    private $mimeTypes;

    public function __construct(MimeTypes $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function __invoke(string $mime): ?string
    {
        return $this->mimeTypes->getExtensions($mime)[0] ?? null;
    }
}
