<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstWaContentContractTest extends TestCase
{
    private const INSTRUCTION = 'Perhatikan lima kata pada setiap soal. Empat kata mempunyai suatu kesamaan. Pilih satu kata yang paling tidak memiliki kesamaan dengan empat kata lainnya.';

    private const PROMPT = 'Pilih satu kata yang paling tidak memiliki kesamaan dengan empat kata lainnya.';

    private const EXPECTED_KEYS = ['C', 'B', 'B', 'D', 'C', 'C', 'C', 'C', 'D', 'D', 'A', 'E', 'A'];

    private const EXPECTED_OPTIONS = [
        ['meja', 'kursi', 'burung', 'lemari', 'tempat tidur'],
        ['lingkaran', 'panah', 'elips', 'busur', 'lengkungan'],
        ['mengetuk', 'memaki', 'menjahit', 'menggergaji', 'memukul'],
        ['lebar', 'keliling', 'luas', 'isi', 'panjang'],
        ['mengikat', 'menyatukan', 'melepaskan', 'mengaitkan', 'melekatkan'],
        ['arah', 'timur', 'perjalanan', 'tujuan', 'selatan'],
        ['jarak', 'perpisahan', 'tugas', 'batas', 'perceraian'],
        ['saringan', 'kelambu', 'payung', 'tapisan', 'jala'],
        ['putih', 'pucat', 'buram', 'kasar', 'berkilauan'],
        ['otobis', 'pesawat terbang', 'sepeda motor', 'sepeda', 'kapal api'],
        ['biola', 'seruling', 'klarinet', 'trompet', 'saxophon'],
        ['bergelombang', 'kasar', 'berduri', 'licin', 'lurus'],
        ['jam', 'kompas', 'penunjuk jalan', 'bintang pari', 'arah'],
    ];

    public function test_wa_canonical_content_matches_source_transcription(): void
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
        $this->assertCount(65, array_merge(...array_column($dataset['questions'], 'options')));
        $this->assertSame(self::INSTRUCTION, $dataset['instruction_content']);
        $this->assertSame(
            array_fill(0, 13, self::PROMPT),
            array_column($dataset['questions'], 'prompt'),
        );
        $this->assertSame(
            self::EXPECTED_OPTIONS,
            array_map(
                static fn (array $question): array => array_column($question['options'], 'text'),
                $dataset['questions'],
            ),
        );
        $this->assertSame(
            self::EXPECTED_KEYS,
            array_map([$this, 'correctKey'], [$example[0], ...$scored]),
        );

        foreach ($dataset['questions'] as $question) {
            $this->assertCount(5, $question['options']);
            $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_column($question['options'], 'key'));
            $this->assertCount(1, array_filter(
                $question['options'],
                static fn (array $option): bool => $option['correct'],
            ));
            $this->assertSame([1], array_values(array_map(
                static fn (array $option): int => $option['score'],
                array_filter($question['options'], static fn (array $option): bool => $option['correct']),
            )));
            $this->assertSame([0], array_values(array_unique(array_map(
                static fn (array $option): int => $option['score'],
                array_filter($question['options'], static fn (array $option): bool => ! $option['correct']),
            ))));
        }
    }

    public function test_wa_difficulty_timer_and_weighted_maximum_are_final(): void
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
        $manifestWa = collect($manifest['subtests'])->firstWhere('code', 'WA');

        $this->assertSame(['easy' => 4, 'medium' => 5, 'hard' => 3], $difficulties);
        $this->assertSame(240, $manifestWa['duration_seconds']);
        $this->assertSame(240, $manifestWa['answering_duration_seconds']);
        $this->assertSame(23, $manifestWa['max_score']);
        $this->assertSame(23.0, $weightedMaximum);
        $this->assertSame(
            ['awarded_score' => 1, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $calculator->scoreBinary(true, 'easy'),
        );
        $this->assertSame(
            ['awarded_score' => 2, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $calculator->scoreBinary(true, 'medium'),
        );
        $this->assertSame(
            ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $calculator->scoreBinary(true, 'hard'),
        );
    }

    private function correctKey(array $question): string
    {
        $correct = array_values(array_filter(
            $question['options'],
            static fn (array $option): bool => $option['correct'],
        ));

        return $correct[0]['key'];
    }

    private function dataset(): array
    {
        return $this->readJson('wa.json');
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
