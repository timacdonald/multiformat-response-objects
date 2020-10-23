<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Support\Collection;

class ResponseType
{
    /**
     * @var array
     */
    private $types;

    /**
     * @param array|string $types
     */
    public function __construct($types)
    {
        $this->types = (array) $types;
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
        return new self($this->options()->merge($type->options())->toArray());
    }

    public function options(): Collection
    {
        return Collection::make($this->types);
    }
}
