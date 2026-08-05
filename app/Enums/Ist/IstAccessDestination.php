<?php

namespace App\Enums\Ist;

enum IstAccessDestination: string
{
    case INSTRUCTION = 'instruction';
    case MEMORIZATION = 'memorization';
    case WORK = 'work';
    case EXPIRED = 'expired';
    case COMPLETED = 'completed';
    case RESULT = 'result';
    case UNAVAILABLE = 'unavailable';
}
