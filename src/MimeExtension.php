<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function is_string;
use Symfony\Component\Mime\MimeTypes as Guesser;

class MimeExtension
{
    /**
     * @var \Symfony\Component\Mime\MimeTypes
     */
    private $guesser;

    /**
     * @var \TiMacDonald\Multiformat\CustomMimeTypes
     */
    private $mimeTypes;

    public function __construct(Guesser $guesser, CustomMimeTypes $mimeTypes)
    {
        $this->guesser = $guesser;

        $this->mimeTypes = $mimeTypes;
    }

    public function __invoke(Request $request): ?string
    {
        $type = Collection::make($request->getAcceptableContentTypes())
            ->map(function (string $contentType): ?string {
                return $this->determineType($contentType);
            })->first(static function (?string $type): bool {
                return $type !== null;
            });

        if ($type === null) {
            return null;
        }

        assert(is_string($type));

        return $type;
    }

    protected function determineType(string $contentType): ?string
    {
        return $this->mimeTypes->find($contentType) ??
            $this->guesser->getExtensions($contentType)[0] ??
            null;
    }
}
