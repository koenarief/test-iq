<?php

namespace App\Exceptions\Ist;

use DomainException;

final class UnsafeIstRehearsalPreviewException extends DomainException
{
    public static function because(string $reason): self
    {
        return new self("IST rehearsal preview ditolak: {$reason}.");
    }
}
