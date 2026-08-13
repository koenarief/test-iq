<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstZrContentContractTest extends TestCase
{
    private const INSTRUCTION = 'Setiap deret tersusun menurut suatu aturan tertentu. Temukan aturannya, lalu masukkan angka berikutnya saja tanpa satuan atau pemisah.';

    private const EXPECTED_PROMPTS = [
        'Tentukan angka berikutnya: 2, 4, 6, 8, 10, 12, 14, ?',
        'Tentukan angka berikutnya: 6, 9, 12, 15, 18, 21, 24, ?',
        'Tentukan angka berikutnya: 15, 16, 18, 19, 21, 22, 24, ?',
        'Tentukan angka berikutnya: 19, 18, 22, 21, 25, 24, 28, ?',
        'Tentukan angka berikutnya: 16, 12, 17, 13, 18, 14, 19, ?',
        'Tentukan angka berikutnya: 2, 4, 8, 10, 20, 22, 44, ?',
        'Tentukan angka berikutnya: 15, 13, 16, 12, 17, 11, 18, ?',
        'Tentukan angka berikutnya: 25, 22, 11, 33, 30, 15, 45, ?',
        'Tentukan angka berikutnya: 49, 51, 54, 27, 9, 11, 14, ?',
        'Tentukan angka berikutnya: 2, 3, 1, 3, 4, 2, 4, ?',
        'Tentukan angka berikutnya: 19, 17, 20, 16, 21, 15, 22, ?',
        'Tentukan angka berikutnya: 94, 92, 46, 44, 22, 20, 10, ?',
        'Tentukan angka berikutnya: 5, 8, 9, 8, 11, 12, 11, ?',
    ];

    private const EXPECTED_ANSWERS = [
        '16', '27', '25', '27', '15', '46', '10', '42', '7', '5', '14', '8', '14',
    ];

    public function test_zr_canonical_content_matches_source_transcription(): void
    {
        $dataset = $this->dataset();
        $example = array_values(array_filter(
            $dataset['questions'],
            static fn (array $question): bool => $question['kind'] === 'example',
        ));
        $scored = array_values(array_filter(
            $dataset['questions'],
            static fn (array $question): bool => $question['kind'] === 'scored',
        ));

        $this->assertCount(1, $example);
        $this->assertCount(12, $scored);
        $this->assertSame(range(1, 12), array_column($scored, 'display_order'));
        $this->assertSame(self::INSTRUCTION, $dataset['instruction_content']);
        $this->assertSame(self::EXPECTED_PROMPTS, array_column($dataset['questions'], 'prompt'));
        $this->assertSame(
            self::EXPECTED_ANSWERS,
            array_column(array_column($dataset['questions'], 'scoring'), 'canonical_answer'),
        );

        foreach ($dataset['questions'] as $question) {
            $this->assertSame('numeric', $question['answer_type']);
            $this->assertSame([], $question['options']);
            $this->assertSame(1, $question['scoring']['max_score']);
            $this->assertMatchesRegularExpression('/\A(?:0|[1-9][0-9]*)\z/', $question['scoring']['canonical_answer']);
        }
    }

    public function test_zr_difficulty_timer_weighted_maximum_and_numeric_scoring_are_final(): void
    {
        $dataset = $this->dataset();
        $manifest = $this->manifest();
        $scored = array_values(array_filter(
            $dataset['questions'],
            static fn (array $question): bool => $question['kind'] === 'scored',
        ));
        $difficulties = array_count_values(array_column($scored, 'difficulty_target'));
        $calculator = new IstScoreCalculator();
        $weightedMaximum = array_sum(array_map(
            static fn (array $question): float => $calculator->weightedMaxScore(
                $question['scoring']['max_score'],
                $question['difficulty_target'],
            ),
            $scored,
        ));
        $manifestZr = collect($manifest['subtests'])->firstWhere('code', 'ZR');

        $this->assertSame(['easy' => 4, 'medium' => 5, 'hard' => 3], $difficulties);
        $this->assertSame(360, $manifestZr['duration_seconds']);
        $this->assertSame(360, $manifestZr['answering_duration_seconds']);
        $this->assertSame(23, $manifestZr['max_score']);
        $this->assertSame(23.0, $weightedMaximum);
        $this->assertSame(
            ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $calculator->scoreNumeric('14.0', '14', 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $calculator->scoreNumeric('13', '14', 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $calculator->scoreNumeric('', '14', 'hard'),
        );
    }

    private function dataset(): array
    {
        return $this->readJson('zr.json');
    }

    private function manifest(): array
    {
        return $this->readJson('manifest.json');
    }

    private function readJson(string $file): array
    {
        $contents = file_get_contents(
            dirname(__DIR__, 3).'/database/data/ist-final-staging/'.$file,
        );

        $this->assertIsString($contents);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}
