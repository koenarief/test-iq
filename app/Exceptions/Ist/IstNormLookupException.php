<?php

namespace App\Exceptions\Ist;

use DomainException;

final class IstNormLookupException extends DomainException
{
    public function __construct(string $scope, int $age, int $score)
    {
        parent::__construct(
            "IST norm data is unavailable for {$scope} at age {$age} (score {$score})."
        );
    }
}
