<?php

namespace TiMacDonald\Multiformat;

use Illuminate\Http\Request;
use TiMacDonald\Multiformat\Contracts\ExtensionGuesser;

class ExplicitExtension implements ExtensionGuesser
{
    /**
     * @var ?string
     */
    private $extension;

    public function __construct(?string $extension)
    {
        $this->extension = $extension;
    }

    public function guess(Request $request): ?string
    {
        return $this->extension;
    }
}
