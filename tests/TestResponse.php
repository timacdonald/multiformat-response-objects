<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Support\Responsable;
use TiMacDonald\Multiformat\SuperResponse;

/**
 * @property string $property
 * @property string $property_1
 * @property string $property_2
 */
class TestResponse implements Responsable
{
    use SuperResponse;

    /**
     * @var string
     */
    public $constructorArg;

    public function __construct(string $constructorArg = '')
    {
        $this->constructorArg = $constructorArg;
    }

    public function toHtmlResponse(): string
    {
        return 'expected html response';
    }

    public function toJsonResponse(): string
    {
        return 'expected json response';
    }

    public function toCsvResponse(): string
    {
        return 'expected csv response';
    }

    public function toXlsxResponse(): string
    {
        return 'expected xlsx response';
    }

    public function toFallbackResponse(): string
    {
        return 'expected fallback response';
    }
}
