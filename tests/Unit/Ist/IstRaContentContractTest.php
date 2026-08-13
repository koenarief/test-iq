<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstRaContentContractTest extends TestCase
{
    private const INSTRUCTION = 'Persoalan berikut adalah soal-soal hitungan. Bacalah setiap soal dengan cermat, hitung hasil akhirnya, lalu masukkan angka saja tanpa satuan atau pemisah ribuan.';

    private const EXPECTED_PROMPTS = [
        'Sebatang pensil harganya 25 rupiah. Berapakah harga 3 batang?',
        'Jika seorang anak memiliki 50 rupiah dan memberikan 15 rupiah kepada orang lain, berapa rupiahkah yang masih tinggal padanya?',
        'Berapa km-kah yang dapat ditempuh oleh kereta api dalam waktu 7 jam, jika kecepatannya 40 km/jam?',
        '15 peti buah-buahan beratnya 250 kg dan setiap peti kosong beratnya 3 kg, berapakah berat buah-buahan itu?',
        'Seseorang mempunyai persediaan rumput yang cukup untuk 7 ekor kuda selama 78 hari. Berapa harikah persediaan itu cukup untuk 21 ekor kuda?',
        '3 batang coklat harganya Rp 5,-. Berapa batangkah yang dapat kita beli dengan Rp 50,-?',
        'Seseorang dapat berjalan 1,75 m dalam waktu ¼ detik. Berapakah meterkah yang dapat ia tempuh dalam waktu 10 detik?',
        'Jika sebuah batu terletak 15 m di sebelah selatan dari sebatang pohon dan pohon itu berada 30 m di sebelah selatan dari sebuah rumah, berapa meterkah jarak antara batu dan rumah itu?',
        'Jika 4 ½ m bahan sandang harganya Rp 90,-, berapakah rupiahkah harganya 2 ½ m?',
        '7 orang dapat menyelesaikan sesuatu pekerjaan dalam 6 hari. Berapa orangkah yang diperlukan untuk menyelesaikan pekerjaan itu dalam setengah hari?',
        'Karena dipanaskan, kawat yang panjangnya 48 cm akan mengembang menjadi 52 cm setelah pemanasan, berapakah panjangnya kawat yang berukuran 72 cm?',
        'Suatu pabrik dapat menghasilkan 304 batang pensil dalam waktu 8 jam. Berapa batangkah dihasilkan dalam waktu setengah jam?',
        'Untuk suatu campuran diperlukan 2 bagian perak dan 3 bagian timah. Berapa gramkah perak yang diperlukan untuk mendapatkan campuran itu yang beratnya 15 gram?',
    ];

    private const EXPECTED_ANSWERS = [
        '75', '35', '280', '205', '26', '30', '70', '45', '50', '84', '78', '19', '6',
    ];

    public function test_ra_canonical_content_matches_source_transcription(): void
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

    public function test_ra_difficulty_timer_weighted_maximum_and_numeric_scoring_are_final(): void
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
        $manifestRa = collect($manifest['subtests'])->firstWhere('code', 'RA');

        $this->assertSame(['easy' => 4, 'medium' => 5, 'hard' => 3], $difficulties);
        $this->assertSame(360, $manifestRa['duration_seconds']);
        $this->assertSame(360, $manifestRa['answering_duration_seconds']);
        $this->assertSame(23, $manifestRa['max_score']);
        $this->assertSame(23.0, $weightedMaximum);
        $this->assertSame(
            ['awarded_score' => 3, 'outcome' => IstAnswer::OUTCOME_CORRECT],
            $calculator->scoreNumeric('78.0', '78', 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_WRONG],
            $calculator->scoreNumeric('77', '78', 'hard'),
        );
        $this->assertSame(
            ['awarded_score' => 0, 'outcome' => IstAnswer::OUTCOME_BLANK],
            $calculator->scoreNumeric('', '78', 'hard'),
        );
    }

    private function dataset(): array
    {
        return $this->readJson('ra.json');
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
