<?php

namespace TiMacDonald\Multiformat;

use Illuminate\Contracts\Support\Responsable;

class BaseMultiformatResponse implements Responsable
{
    use Multiformat;
}
