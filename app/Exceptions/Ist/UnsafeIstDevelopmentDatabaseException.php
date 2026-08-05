<?php

namespace App\Exceptions\Ist;

use DomainException;

final class UnsafeIstDevelopmentDatabaseException extends DomainException
{
    public static function because(string $reason): self
    {
        return new self("Operasi dataset development IST ditolak: {$reason}");
    }
}
