<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstAutosaveException extends DomainException
{
    public function __construct(int $testSubtestId, string $mismatch)
    {
        parent::__construct(
            "Cannot autosave IST runtime subtest {$testSubtestId}: {$mismatch}."
        );
    }
}
