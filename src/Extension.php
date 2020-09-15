<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function is_string;
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
    public function __construct(array $guessers)
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
            ->reduce(static function (?string $carry, ExtensionGuesser $guesser) use ($request): ?string {
                return $carry ?? $guesser->guess($request);
            }, null);

        assert(is_string($extension));

        // do we even need this? Maybe we should just make it that
        // you have to be explicit. Need to brainstorm this a little more.
        if ($extension === 'html') {
            return $this->fallbackExtension($fallbackExtension);
        }

        return $extension;
    }

    private function fallbackExtension(?FallbackExtension $fallbackExtension): string
    {
        if ($fallbackExtension === null) {
            return $this->fallbackExtension->value();
        }

        return $fallbackExtension->value();
    }
}
