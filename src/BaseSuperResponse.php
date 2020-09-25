<?php

declare(strict_types=1);

namespace TiMacDonald\Multiformat;

use Illuminate\Contracts\Support\Responsable;

class BaseSuperResponse implements Responsable
{
    use SuperResponse;
}
