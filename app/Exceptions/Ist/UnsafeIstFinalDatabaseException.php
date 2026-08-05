<?php

namespace App\Exceptions\Ist;

use DomainException;

final class UnsafeIstFinalDatabaseException extends DomainException
{
    public static function because(string $reason): self
    {
        return new self("Operasi dataset final ditolak oleh database guard: {$reason}");
    }
}
