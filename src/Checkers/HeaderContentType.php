<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat\Checkers;

use Illuminate\Http\Request;
use TiMacDonald\Multiformat\Contracts\MimeToType;
use TiMacDonald\Multiformat\ResponseType;

class HeaderContentType
{
    use Concerns\DetectValidMethodStrings;

    /**
     * @var \TiMacDonald\Multiformat\Contracts\MimeToType
     */
    private $mimeToType;

    public function __construct(MimeToType $mimeToType)
    {
        $this->mimeToType = $mimeToType;
    }

    public function __invoke(Request $request): ?ResponseType
    {
        /** @var string $contentType */
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            $type = ($this->mimeToType)($contentType);

            if ($type === null) {
                continue;
            }

            if (self::containsSomeValidMethodCharacters($type)) {
                return new ResponseType($type);
            }
        }

        return null;
    }
}
