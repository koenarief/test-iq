<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstGeContentContractTest extends TestCase
{
    private const INSTRUCTION = 'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut. Beberapa pilihan mungkin masih berhubungan, tetapi pilih konsep yang paling tepat dan paling spesifik untuk keduanya.';

    private const EXPECTED_KEYS = ['B', 'B', 'D', 'C', 'A', 'D', 'B', 'D', 'A', 'C', 'E'];

    private const EXPECTED_PROMPTS = [
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: ayam — itik.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: mawar — melati.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: mata — telinga.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: gula — intan.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: hujan — salju.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: pengantar surat — telepon.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: kamera — kacamata.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: lambung — usus.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: banyak — sedikit.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: telur — benih.',
        'Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: bendera — lencana.',
    ];

    private const EXPECTED_OPTIONS = [
        [['Hewan', 1], ['Burung', 3], ['Hewan ternak', 2], ['Petelur', 0], ['Kandang', 0]],
        [['Tanaman', 1], ['Bunga', 3], ['Tumbuhan hias', 2], ['Kebun', 0], ['Harum', 0]],
        [['Indra', 2], ['Organ tubuh', 1], ['Kepala', 0], ['Pancaindra', 3], ['Wajah', 0]],
        [['Benda padat', 1], ['Benda bening', 2], ['Kristal', 3], ['Mineral', 0], ['Perhiasan', 0]],
        [['Presipitasi', 3], ['Fenomena cuaca', 2], ['Air', 1], ['Awan', 0], ['Dingin', 0]],
        [['Percakapan', 0], ['Penghubung', 2], ['Sarana komunikasi', 1], ['Penyampai pesan', 3], ['Kantor', 0]],
        [['Penglihatan', 0], ['Alat optik', 3], ['Peralatan berlensa', 2], ['Benda buatan', 1], ['Fotografi', 0]],
        [['Bagian tubuh', 1], ['Rongga perut', 0], ['Organ dalam', 2], ['Organ pencernaan', 3], ['Penyerap makanan', 0]],
        [['Keterangan kuantitas', 3], ['Ukuran jumlah', 2], ['Besaran', 1], ['Bilangan', 0], ['Urutan', 0]],
        [['Hasil perkembangbiakan', 1], ['Awal kehidupan', 2], ['Calon individu baru', 3], ['Bahan pangan', 0], ['Tumbuhan', 0]],
        [['Penanda', 1], ['Hiasan', 0], ['Tanda identitas', 2], ['Kain', 0], ['Lambang', 3]],
    ];

    public function test_ge_canonical_content_and_weight_hierarchy_are_final(): void
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
        $this->assertCount(10, $scored);
        $this->assertSame(range(1, 10), array_column($scored, 'display_order'));
        $this->assertCount(55, array_merge(...array_column($dataset['questions'], 'options')));
        $this->assertSame(self::INSTRUCTION, $dataset['instruction_content']);
        $this->assertSame(self::EXPECTED_PROMPTS, array_column($dataset['questions'], 'prompt'));
        $this->assertSame(
            self::EXPECTED_OPTIONS,
            array_map(
                static fn (array $question): array => array_map(
                    static fn (array $option): array => [$option['text'], $option['score']],
                    $question['options'],
                ),
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
            $scoreCounts = array_count_values(array_column($question['options'], 'score'));
            ksort($scoreCounts);
            $this->assertSame([0 => 2, 1 => 1, 2 => 1, 3 => 1], $scoreCounts);
            $correct = array_values(array_filter(
                $question['options'],
                static fn (array $option): bool => $option['correct'],
            ));
            $this->assertCount(1, $correct);
            $this->assertSame(3, $correct[0]['score']);

            foreach ($question['options'] as $option) {
                $this->assertSame($option['score'] === 3, $option['correct']);
            }
        }
    }

    public function test_ge_difficulty_timer_and_weighted_maximum_are_final(): void
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
        $manifestGe = collect($manifest['subtests'])->firstWhere('code', 'GE');

        $this->assertSame(['easy' => 3, 'medium' => 4, 'hard' => 3], $difficulties);
        $this->assertSame(300, $manifestGe['duration_seconds']);
        $this->assertSame(300, $manifestGe['answering_duration_seconds']);
        $this->assertSame(60, $manifestGe['max_score']);
        $this->assertSame(60.0, $weightedMaximum);
        $this->assertSame(
            ['awarded_score' => 9, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $calculator->scoreWeighted(3, 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 6, 'outcome' => IstAnswer::OUTCOME_PARTIAL],
            $calculator->scoreWeighted(2, 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_PARTIAL],
            $calculator->scoreWeighted(1, 'hard'),
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
        return $this->readJson('ge.json');
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
