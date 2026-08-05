<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstFinalQuestionDatasetException extends DomainException
{
    public static function because(string $reason): self
    {
        return new self("Dataset final asesmen kognitif ditolak: {$reason}");
    }
}
