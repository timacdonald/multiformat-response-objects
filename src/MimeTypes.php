<?php

namespace TiMacDonald\Multiformat;

class MimeTypes
{
    /**
     * @var string[]
     */
    private $value;

    /**
     * @param string[] $value
     */
    public function __construct(array $value)
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
