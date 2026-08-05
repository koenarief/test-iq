<?php

namespace App\Enums\Ist;

enum IstFinalizationReason: string
{
    case SUBMITTED = 'submitted';

    case TIMEOUT = 'timeout';
}
