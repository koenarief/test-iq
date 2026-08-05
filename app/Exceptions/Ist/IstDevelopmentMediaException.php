<?php

namespace App\Exceptions\Ist;

use RuntimeException;

final class IstDevelopmentMediaException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self("Media development IST gagal diproses: {$reason}");
    }
}
