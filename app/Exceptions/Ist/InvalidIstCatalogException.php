<?php

namespace App\Exceptions\Ist;

use DomainException;

final class InvalidIstCatalogException extends DomainException
{
    public function __construct(string $mismatch)
    {
        parent::__construct("Invalid IST catalog: {$mismatch}.");
    }
}
