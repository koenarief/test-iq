<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstAnContentContractTest extends TestCase
{
    private const INSTRUCTION = 'Perhatikan hubungan antara kata pertama dan kata kedua. Pilih satu kata A–E yang mempunyai hubungan paling tepat dengan kata ketiga, dengan pola hubungan yang sama: Kata 1 : Kata 2 = Kata 3 : ?.';

    private const EXPECTED_KEYS = ['A', 'C', 'E', 'D', 'D', 'D', 'B', 'D', 'B', 'D', 'D', 'C', 'C'];

    private const EXPECTED_PROMPTS = [
        'Hutan : pohon = tembok : ?',
        'Menemukan : menghilangkan = Mengingat : ?',
        'Bunga : jambangan = Burung : ?',
        'Kereta api : rel = Otobis : ?',
        'Perak : emas = Cincin : ?',
        'Lingkaran : bola = Bujur sangkar : ?',
        'Saran : keputusan = Merundingkan : ?',
        'Lidah : asam = Hidung : ?',
        'Darah : pembuluh = Air : ?',
        'Saraf : penyalur = Pupil : ?',
        'Pengantar surat : pengantar telegram = Pandai besi : ?',
        'Buta : warna = Tuli : ?',
        'Makanan : bumbu = Ceramah : ?',
    ];

    private const EXPECTED_OPTIONS = [
        ['batu bata', 'rumah', 'semen', 'putih', 'dinding'],
        ['menghapal', 'mengenai', 'melupakan', 'berpikir', 'menimpikan'],
        ['sarang', 'langit', 'pagar', 'pohon', 'sangkar'],
        ['roda', 'poros', 'ban', 'jalan raya', 'kecepatan'],
        ['arloji', 'berlian', 'permata', 'gelang', 'platina'],
        ['bentuk', 'gambar', 'segi empat', 'kubus', 'piramida'],
        ['menawarkan', 'menentukan', 'menilai', 'menimbang', 'merenungkan'],
        ['mencium', 'bernafas', 'mengecap', 'tengik', 'asin'],
        ['pintu air', 'sungai', 'talang', 'hujan', 'ember'],
        ['penyinaran', 'mata', 'melihat', 'cahaya', 'pelindung'],
        ['palu godam', 'pedagang besi', 'api', 'tukang emas', 'besi tempa'],
        ['pendengaran', 'mendengar', 'nada', 'kata', 'telinga'],
        ['penghinaan', 'pidato', 'kelakar', 'kesan', 'ayat'],
    ];

    public function test_an_canonical_content_matches_source_transcription(): void
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
        $this->assertSame(self::EXPECTED_PROMPTS, array_column($dataset['questions'], 'prompt'));
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

    public function test_an_difficulty_timer_and_weighted_maximum_are_final(): void
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
        $manifestAn = collect($manifest['subtests'])->firstWhere('code', 'AN');

        $this->assertSame(['easy' => 4, 'medium' => 5, 'hard' => 3], $difficulties);
        $this->assertSame(240, $manifestAn['duration_seconds']);
        $this->assertSame(240, $manifestAn['answering_duration_seconds']);
        $this->assertSame(23, $manifestAn['max_score']);
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
        return $this->readJson('an.json');
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
