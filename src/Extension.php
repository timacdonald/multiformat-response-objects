<?php

namespace TiMacDonald\Multiformat;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\Multiformat\Contracts\Extension as ExtensionContract;
use TiMacDonald\Multiformat\Contracts\ExtensionGuesser;

class Extension implements ExtensionContract
{
    /**
     * @var \TiMacDonald\Multiformat\Contracts\ExtensionGuesser[]
     */
    private $guessers;

    /**
     * @var \TiMacDonald\Multiformat\FallbackExtension
     */
    private $fallbackExtension;

    /**
     * @param \TiMacDonald\Multiformat\Contracts\ExtensionGuesser[] $guessers
     */
    public function __construct(array $guessers, FallbackExtension $fallbackExtension)
    {
        $this->guessers = $guessers;

        $this->fallbackExtension = $fallbackExtension;
    }

    public function parse(Request $request, ?FallbackExtension $fallbackExtension): string
    {
        $extension = Collection::make($this->guessers)
            ->merge([
                new ExplicitExtension($fallbackExtension ? $fallbackExtension->value() : null),
                new ExplicitExtension($this->fallbackExtension->value()),
            ])
            ->reduce(function (?string $carry, ExtensionGuesser $guesser) use ($request): ?string {
                return $carry ?? $guesser->guess($request);
            }, null);

        assert(is_string($extension));

        return $extension;
    }
}
