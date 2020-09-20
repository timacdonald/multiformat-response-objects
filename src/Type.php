<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use function assert;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function is_string;

class Type
{
    /**
     * @var callable[]
     */
    private $checkers;

    /**
     * @param callable[] $checkers
     */
    public function __construct(array $checkers)
    {
        $this->checkers = $checkers;
    }

    public function check(Request $request): ?string
    {
        $type = Collection::make($this->checkers)
            ->reduce(static function (?string $carry, callable $guesser) use ($request): ?string {
                $type = $carry ?? $guesser($request);

                assert(is_string($type) || $type === null);

                return $type;
            }, null);

        if ($type === null) {
            return null;
        }

        assert(is_string($type));

        return $type;
    }
}
