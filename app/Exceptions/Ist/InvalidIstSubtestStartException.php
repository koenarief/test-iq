<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstSubtestStartException extends DomainException
{
    public function __construct(int $testSubtestId, string $mismatch)
    {
        parent::__construct(
            "Cannot start IST runtime subtest {$testSubtestId}: {$mismatch}."
        );
    }
}
