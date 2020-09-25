<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Support\Str;

use function trim;

class ResponseType
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        $this->type = Str::ucfirst(trim($type));
    }

    public static function makeUnknown(): self
    {
        return new self('');
    }

    public function isKnown(): bool
    {
        return $this->type !== '';
    }

    public function isUnknown(): bool
    {
        return ! $this->isKnown();
    }

    public function add(string $type): self
    {
        return new self($this->type.Str::ucfirst($type));
    }

    public function value(): string
    {
        return $this->type;
    }
}
