<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Contracts\Support\Responsable;

class BaseMultiformatResponse implements Responsable
{
    use Multiformat;
}
