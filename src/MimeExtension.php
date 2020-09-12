<?php

namespace TiMacDonald\Multiformat;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\MimeTypes as Guesser;
use TiMacDonald\Multiformat\Contracts\ExtensionGuesser;

class MimeExtension implements ExtensionGuesser
{
    /**
     * @var \Symfony\Component\Mime\MimeTypes
     */
    private $guesser;

    /**
     * @var \TiMacDonald\Multiformat\MimeTypes
     */
    private $mimeTypes;

    public function __construct(Guesser $guesser, MimeTypes $mimeTypes)
    {
        $this->guesser = $guesser;

        $this->mimeTypes = $mimeTypes;
    }

    public function guess(Request $request): ?string
    {
        $extension = Collection::make($request->getAcceptableContentTypes())
            ->map(function (string $contentType): ?string {
                return $this->findContentTypeExtension($contentType);
            })->first(function (?string $extension): bool {
                return $extension !== null;
            });

        if ($extension === null) {
            return null;
        }

        assert(is_string($extension));

        return $extension;
    }

    private function findContentTypeExtension(string $contentType): ?string
    {
        return $this->mimeTypes->value()[$contentType] ??
            $this->guesser->getExtensions($contentType)[0] ??
            null;
    }
}
