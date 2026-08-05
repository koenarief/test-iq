<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstQuestionDefinitionException extends DomainException
{
    public function __construct(
        string $subtestCode,
        int $questionReference,
        string $mismatch,
    ) {
        parent::__construct(
            "Invalid IST question definition for {$subtestCode} question {$questionReference}: {$mismatch}."
        );
    }
}
