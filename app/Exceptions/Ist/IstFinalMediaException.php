<?php

namespace App\Exceptions\Ist;

use RuntimeException;

final class IstFinalMediaException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self("Media dataset final gagal diproses: {$reason}");
    }
}
