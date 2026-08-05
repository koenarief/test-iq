<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstQuestionDatasetException extends DomainException
{
    public static function because(string $reason): self
    {
        return new self("IST development dataset ditolak: {$reason}");
    }
}
