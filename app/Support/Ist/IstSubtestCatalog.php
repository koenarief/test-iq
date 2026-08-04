<?php

namespace App\Support\Ist;

final class IstSubtestCatalog
{
    public const EXPECTED_SUBTEST_COUNT = 9;

    public const EXPECTED_QUESTION_COUNT = 104;

    public const EXPECTED_CORE_DURATION_SECONDS = 2700;

    public static function all(): array
    {
        return [
            [
                'code' => 'SE',
                'name' => 'Satzerganzung (Melengkapi Kalimat)',
                'sequence' => 1,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 240,
                'memorization_seconds' => 0,
                'answering_seconds' => 240,
            ],
            [
                'code' => 'WA',
                'name' => 'Wortauswahl (Memilih Kata)',
                'sequence' => 2,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 240,
                'memorization_seconds' => 0,
                'answering_seconds' => 240,
            ],
            [
                'code' => 'AN',
                'name' => 'Analogien (Analogi)',
                'sequence' => 3,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 240,
                'memorization_seconds' => 0,
                'answering_seconds' => 240,
            ],
            [
                'code' => 'GE',
                'name' => 'Gemeinsamkeiten (Persamaan)',
                'sequence' => 4,
                'question_count' => 10,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
                'duration_seconds' => 300,
                'memorization_seconds' => 0,
                'answering_seconds' => 300,
            ],
            [
                'code' => 'RA',
                'name' => 'Rechenaufgaben (Berhitung)',
                'sequence' => 5,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::NUMERIC,
                'duration_seconds' => 360,
                'memorization_seconds' => 0,
                'answering_seconds' => 360,
            ],
            [
                'code' => 'ZR',
                'name' => 'Zahlenreihen (Deret Angka)',
                'sequence' => 6,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::NUMERIC,
                'duration_seconds' => 360,
                'memorization_seconds' => 0,
                'answering_seconds' => 360,
            ],
            [
                'code' => 'FA',
                'name' => 'Figurenauswahl (Memilih Bentuk)',
                'sequence' => 7,
                'question_count' => 10,
                'default_answer_type' => IstAnswerType::IMAGE_CHOICE,
                'duration_seconds' => 240,
                'memorization_seconds' => 0,
                'answering_seconds' => 240,
            ],
            [
                'code' => 'WU',
                'name' => 'Wurfelaufgaben (Latihan Kubus)',
                'sequence' => 8,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::IMAGE_CHOICE,
                'duration_seconds' => 360,
                'memorization_seconds' => 0,
                'answering_seconds' => 360,
            ],
            [
                'code' => 'ME',
                'name' => 'Merkaufgaben (Latihan Mengingat)',
                'sequence' => 9,
                'question_count' => 12,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 360,
                'memorization_seconds' => 120,
                'answering_seconds' => 240,
            ],
        ];
    }
}
