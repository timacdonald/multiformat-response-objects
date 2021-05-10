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
     * @param \TiMacDonald\Multiformat\Contracts\ExtensionGuesser[] $guessers
     */
    public function __construct(array $guessers)
    {
        $this->guessers = $guessers;
    }

    public function parse(Request $request): ?string
    {
        $extension = Collection::make($this->guessers)
            ->reduce(static function (?string $carry, ExtensionGuesser $guesser) use ($request): ?string {
                return $carry ?? $guesser->guess($request);
            }, null);

        if ($extension === null) {
            return null;
        }

        assert(is_string($extension));

        return $extension;
    }
}
