<?php

namespace App\Exceptions\Ist;

use DomainException;

final class IstAnswerRevisionConflictException extends DomainException
{
    public function __construct(int $testSubtestId, int $testQuestionId)
    {
        parent::__construct(
            "IST answer revision conflict for runtime subtest {$testSubtestId}, question {$testQuestionId}."
        );
    }
}
