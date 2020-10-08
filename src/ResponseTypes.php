<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Support\Collection;

class ResponseTypes
{
    /**
     * @var array
     */
    private $types;

    public function __construct(array $types)
    {
        $this->types = $types;
    }

    public static function makeUnknown(): self
    {
        return new self([]);
    }

    public function isKnown(): bool
    {
        return $this->types !== [];
    }

    public function isUnknown(): bool
    {
        return ! $this->isKnown();
    }

    public function add(self $type): self
    {
        return new self($this->value()->merge($type->value())->toArray());
    }

    public function value(): Collection
    {
        return Collection::make($this->types);
    }
}
