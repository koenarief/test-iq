<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstFinalizationException extends DomainException
{
    public function __construct(int $testSubtestId, string $mismatch)
    {
        parent::__construct(
            "Cannot finalize IST runtime subtest {$testSubtestId}: {$mismatch}."
        );
    }
}
