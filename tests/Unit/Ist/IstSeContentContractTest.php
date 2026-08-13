<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Support\Ist\IstScoreCalculator;
use PHPUnit\Framework\TestCase;

final class IstSeContentContractTest extends TestCase
{
    private const INSTRUCTION = 'Bacalah setiap kalimat atau pernyataan. Pada setiap soal tersedia lima pilihan jawaban A–E. Pilih satu jawaban yang paling tepat untuk melengkapi atau menjawab pernyataan.';

    private const EXPECTED_KEYS = ['C', 'E', 'C', 'D', 'D', 'D', 'B', 'C', 'A', 'E', 'B', 'C', 'D'];

    private const EXPECTED_PROMPTS = [
        'Seekor kuda mempunyai kesamaan terbanyak dengan seekor .....',
        'Pengaruh seseorang terhadap orang lain seharusnya bergantung pada .....',
        'Lawannya “hemat” ialah .....',
        '..... tidak termasuk cuaca',
        'Lawannya “setia” ialah .....',
        'Seekor kuda selalu mempunyai .....',
        'Seorang paman ..... lebih tua dari kemenakannya.',
        'Pada jumlah yang sama, nilai kalori yang tertinggi terdapat pada .....',
        'Pada suatu pertandingan selalu terdapat .....',
        'Suatu pernyataan yang belum dipastikan dikatakan sebagai pernyataan yang .....',
        'Pada sepatu selalu terdapat .....',
        'Suatu ..... tidak menyangkut persoalan pencegahan kecelakaan.',
        'Mata uang logam Rp 50,- tahun 1991, garis tengahnya ialah ..... mm.',
    ];

    private const EXPECTED_OPTIONS = [
        ['kucing', 'bajing', 'keledai', 'lembu', 'anjing'],
        ['kekuasaan', 'bujukan', 'kekayaan', 'keberanian', 'kewibawaan'],
        ['murah', 'kikir', 'boros', 'bernilai', 'kaya'],
        ['angin puyuh', 'halilintar', 'salju', 'gempa bumi', 'kabut'],
        ['cinta', 'benci', 'persahabatan', 'khianat', 'permusuhan'],
        ['kandang', 'ladam', 'pelana', 'kuku', 'surai'],
        ['jarang', 'biasanya', 'selalu', 'tidak pernah', 'kadang-kadang'],
        ['ikan', 'daging', 'lemak', 'tahu', 'sayuran'],
        ['lawan', 'wasit', 'penonton', 'sorak', 'kemenangan'],
        ['paradoks', 'tergesa-gesa', 'mempunyai arti rangkap', 'menyesatkan', 'hipotesis'],
        ['kulit', 'sol', 'tali sepatu', 'gesper', 'lidah'],
        ['lampu lalu lintas', 'kacamata pelindung', 'kotak PPPK', 'tanda peringatan', 'palang kereta api'],
        ['17', '29', '25', '20', '15'],
    ];

    private const LEGACY_PROMPTS = [
        'Petugas menutup jendela agar air hujan tidak ___ ke dalam ruangan.',
        'Lampu lalu lintas berubah merah, sehingga pengendara harus ___.',
        'Rapat evaluasi baru dapat dimulai setelah seluruh data berhasil ___.',
        'Menjelang perjalanan, awan gelap membuat langit tampak ___.',
        'Usulan itu terdengar menarik, tetapi belum dapat diterapkan karena uraian langkah kerjanya masih ___.',
        'Jalan utama ditutup sementara, maka pengemudi perlu mencari rute ___.',
        'Agar dokumen mudah ditemukan kembali, berkas disusun secara ___.',
        'Dengan memprioritaskan langkah penting, tim dapat bekerja lebih ___ meskipun waktu persiapan singkat.',
        'Pernyataan itu tampak meyakinkan, namun bukti yang diberikan belum cukup ___ kesimpulannya.',
        'Setelah hujan berhenti, udara terasa lebih ___.',
        'Petunjuk diringkas supaya pembaca dapat memahami urutan kerja secara ___.',
        'Kedua rencana menawarkan manfaat serupa, sehingga keputusan perlu dibuat berdasarkan kriteria yang paling ___ dengan tujuan utama.',
        'Sebelum mengirim laporan, Nara memeriksa ulang angka-angkanya untuk ___ kesalahan terbawa ke versi akhir.',
    ];

    public function test_se_canonical_content_has_one_example_and_twelve_scored_items(): void
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

    public function test_se_difficulty_timer_and_weighted_maximum_are_final(): void
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
        $manifestSe = collect($manifest['subtests'])->firstWhere('code', 'SE');

        $this->assertSame(['easy' => 4, 'medium' => 5, 'hard' => 3], $difficulties);
        $this->assertSame(240, $manifestSe['duration_seconds']);
        $this->assertSame(240, $manifestSe['answering_duration_seconds']);
        $this->assertSame(23, $manifestSe['max_score']);
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

    public function test_no_legacy_se_prompt_remains_in_canonical_staging(): void
    {
        $prompts = array_column($this->dataset()['questions'], 'prompt');

        foreach (self::LEGACY_PROMPTS as $legacyPrompt) {
            $this->assertNotContains($legacyPrompt, $prompts);
        }
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
        return $this->readJson('se.json');
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
