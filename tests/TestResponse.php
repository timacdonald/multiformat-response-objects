<?php

declare(strict_types=1);

namespace Tests;

use TiMacDonald\Multiformat\BaseSuperResponse;

/**
 * @property string $property
 * @property string $property_1
 * @property string $property_2
 */
class TestResponse extends BaseSuperResponse
{
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
}
