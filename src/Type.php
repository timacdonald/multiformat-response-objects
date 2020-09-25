<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Http\Request;

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

    public function check(Request $request, array $typeCheckers = []): ResponseType
    {
    }
}
