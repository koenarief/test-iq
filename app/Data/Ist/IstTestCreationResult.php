<?php

namespace App\Data\Ist;

use App\Models\Ist\IstTest;
use LogicException;
use SensitiveParameter;

final class IstTestCreationResult
{
    private ?string $rawAccessToken;

    public function __construct(
        public readonly IstTest $test,
        #[SensitiveParameter] string $rawAccessToken,
    ) {
        $this->rawAccessToken = $rawAccessToken;
    }

    public function takeRawAccessToken(): string
    {
        if ($this->rawAccessToken === null) {
            throw new LogicException('The IST access token has already been consumed.');
        }

        $token = $this->rawAccessToken;
        $this->rawAccessToken = null;

        return $token;
    }

    public function __debugInfo(): array
    {
        return [
            'test' => $this->test,
            'rawAccessToken' => '[HIDDEN]',
        ];
    }
}
