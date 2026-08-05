<?php

namespace App\Exceptions\Ist;

use DomainException;

final class IncompleteIstSnapshotException extends DomainException
{
    public function __construct(int $testSubtestId, int $expected, int $actual)
    {
        parent::__construct(
            "Incomplete IST snapshot for runtime subtest {$testSubtestId}: expected {$expected}, found {$actual}."
        );
    }
}
