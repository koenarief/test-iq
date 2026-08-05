<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstSubtestAccessStateException extends DomainException
{
    public function __construct(int $testId, string $mismatch)
    {
        parent::__construct(
            "Invalid IST access state for test {$testId}: {$mismatch}."
        );
    }
}
