<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstQuestionCountException extends DomainException
{
    public function __construct(string $subtestCode, int $expected, int $actual)
    {
        parent::__construct(
            "Invalid IST question count for {$subtestCode}: expected {$expected}, found {$actual}."
        );
    }
}
