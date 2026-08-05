<?php

namespace App\Enums\Ist;

enum IstSubtestPhase: string
{
    case INSTRUCTION = 'instruction';
    case MEMORIZING = 'memorizing';
    case ANSWERING = 'answering';
    case COMPLETED = 'completed';
    case TIMED_OUT = 'timed_out';
}
