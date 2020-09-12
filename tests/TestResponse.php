<?php

namespace Tests;

use Illuminate\Contracts\Support\Responsable;
use TiMacDonald\Multiformat\BaseMultiformatResponse;
use TiMacDonald\Multiformat\Multiformat;

/**
 * @property string $property
 * @property string $property_1
 * @property string $property_2
 */
class TestResponse extends BaseMultiformatResponse
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
