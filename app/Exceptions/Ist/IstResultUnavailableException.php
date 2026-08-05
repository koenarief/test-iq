<?php

namespace App\Exceptions\Ist;

use DomainException;

final class IstResultUnavailableException extends DomainException
{
    public function __construct(int $testId, string $mismatch)
    {
        parent::__construct(
            "IST result is unavailable for test {$testId}: {$mismatch}."
        );
    }
}
