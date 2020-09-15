<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

class CustomMimeTypes
{
    /**
     * @var string[]
     */
    private $value;

    /**
     * @param string[] $value
     */
    public function __construct(array $value = [])
    {
        $this->value = $value;
    }

    /**
     * @return string[]
     */
    public function value(): array
    {
        return $this->value;
    }
}
