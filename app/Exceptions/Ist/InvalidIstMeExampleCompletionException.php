<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstMeExampleCompletionException extends DomainException
{
    public function __construct(int $testSubtestId, string $reason)
    {
        parent::__construct("IST ME example completion invalid for runtime {$testSubtestId}: {$reason}");
    }
}
