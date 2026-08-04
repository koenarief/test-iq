<?php

namespace App\Support\Ist;

final class IstAnswerType
{
    public const SINGLE_CHOICE = 'single_choice';

    public const SINGLE_CHOICE_WEIGHTED = 'single_choice_weighted';

    public const NUMERIC = 'numeric';

    public const IMAGE_CHOICE = 'image_choice';

    public static function all(): array
    {
        return [
            self::SINGLE_CHOICE,
            self::SINGLE_CHOICE_WEIGHTED,
            self::NUMERIC,
            self::IMAGE_CHOICE,
        ];
    }
}
