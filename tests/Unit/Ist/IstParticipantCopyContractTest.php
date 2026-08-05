<?php

namespace Tests\Unit\Ist;

use PHPUnit\Framework\TestCase;

final class IstParticipantCopyContractTest extends TestCase
{
    private const PRODUCT_NAME = 'Tes Kemampuan Kognitif Adaptasi';

    private const DISCLAIMER = 'Hasil ini merupakan skor internal berdasarkan sembilan subtes. Nilai ini belum merupakan skor IQ atau interpretasi normatif.';

    public function test_biodata_uses_product_copy_without_legacy_participant_labels(): void
    {
        $source = $this->source('resources/js/Pages/IST/Biodata.jsx');

        $this->assertStringContainsString('<Head title="'.self::PRODUCT_NAME.'" />', $source);
        $this->assertStringContainsString('Isi Data Peserta', $source);
        $this->assertStringContainsString('Lengkapi data berikut sebelum memulai asesmen.', $source);
        $this->assertStringContainsString('waktu pengerjaan inti sekitar 45 menit', $source);
        $this->assertLegacyParticipantCopyAbsent($source);
    }

    public function test_instruction_uses_product_and_subtest_copy_without_legacy_labels(): void
    {
        $source = $this->source('resources/js/Pages/IST/Instruction.jsx');

        $this->assertStringContainsString(self::PRODUCT_NAME, $source);
        $this->assertStringContainsString('Petunjuk Subtes', $source);
        $this->assertStringContainsString('Fase menghafal', $source);
        $this->assertStringContainsString('Fase menjawab', $source);
        $this->assertStringContainsString('!hasMemorizationPhase && hasAnsweringDuration', $source);
        $this->assertLegacyParticipantCopyAbsent($source);
    }

    public function test_work_and_accessibility_copy_are_neutral(): void
    {
        $work = $this->source('resources/js/Pages/IST/Work.jsx');
        $question = $this->source('resources/js/Components/IST/IstQuestionCard.jsx');
        $image = $this->source('resources/js/Components/IST/IstImageViewer.jsx');

        $this->assertStringContainsString(self::PRODUCT_NAME, $work);
        $this->assertStringContainsString('Pengerjaan Subtes', $question);
        $this->assertStringContainsString("fallbackAlt = 'Ilustrasi soal'", $image);
        $this->assertStringContainsString('Ilustrasi pilihan', $question);
        $this->assertLegacyParticipantCopyAbsent($work.$question.$image);
    }

    public function test_result_uses_internal_score_labels_and_exact_disclaimer(): void
    {
        $source = $this->source('resources/js/Pages/IST/Result.jsx');

        $this->assertStringContainsString('<Head title="Hasil '.self::PRODUCT_NAME.'" />', $source);
        $this->assertStringContainsString('Ringkasan Hasil', $source);
        $this->assertStringContainsString('Rata-rata Skor Internal', $source);
        $this->assertStringContainsString('Rata-rata persentase dari sembilan subtes.', $source);
        $this->assertStringContainsString('Profil Sembilan Subtes', $source);
        $this->assertStringContainsString('Rincian hasil sembilan subtes', $source);
        $this->assertSame(1, substr_count($source, self::DISCLAIMER));
        $this->assertSame(1, preg_match_all('/\bIQ\b/', $source));
        $this->assertLegacyParticipantCopyAbsent($source);
    }

    public function test_landing_uses_final_product_copy_and_remains_inactive(): void
    {
        $source = $this->source('resources/js/Pages/Landing/Index.jsx');

        $this->assertStringContainsString('title="'.self::PRODUCT_NAME.'"', $source);
        $this->assertStringContainsString('Mengukur performa pada sembilan area kemampuan kognitif melalui asesmen singkat sekitar 45 menit.', $source);
        $this->assertStringContainsString('Asesmen singkat untuk melihat profil performa pada sembilan area kemampuan kognitif.', $source);
        $this->assertStringContainsString('Durasi: Sekitar 45 menit', $source);
        $this->assertMatchesRegularExpression(
            '/title="Tes Kemampuan Kognitif Adaptasi"[\s\S]*?isActive=\{false\}/',
            $source,
        );
        $this->assertStringNotContainsString('90 Menit', $source);
        $this->assertLegacyParticipantCopyAbsent($source);
    }

    public function test_http_copy_is_generic_while_internal_codes_and_routes_remain_ist(): void
    {
        $httpSources = implode("\n", array_map(
            fn (string $path): string => $this->source($path),
            [
                'app/Http/Controllers/Ist/IstTestController.php',
                'app/Http/Controllers/Ist/IstSubtestInstructionController.php',
                'app/Http/Controllers/Ist/IstSubtestSessionController.php',
                'app/Http/Controllers/Ist/IstResultController.php',
                'app/Http/Support/Ist/IstCanonicalNavigator.php',
            ],
        ));
        $answerController = $this->source('app/Http/Controllers/Ist/IstAnswerController.php');
        $routes = $this->source('routes/ist.php');

        $this->assertDoesNotMatchRegularExpression('/abort\([^;]+[\'\"]IST\b/s', $httpSources);
        $this->assertStringContainsString('Asesmen belum tersedia.', $httpSources);
        $this->assertStringContainsString("'IST_ANSWER_REVISION_CONFLICT'", $answerController);
        $this->assertStringContainsString("Route::prefix('ist')->name('ist.')", $routes);
    }

    public function test_chart_order_and_value_contract_remain_unchanged(): void
    {
        $source = $this->source('resources/js/Components/IST/IstResultChart.jsx');

        $this->assertStringContainsString(
            "const SUBTEST_ORDER = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];",
            $source,
        );
        $this->assertStringContainsString('percentage: Number.isFinite(numericPercentage)', $source);
        $this->assertStringContainsString('awardedScore: subtest?.awardedScore', $source);
        $this->assertStringContainsString('maxScore: subtest?.maxScore', $source);
    }

    private function assertLegacyParticipantCopyAbsent(string $source): void
    {
        foreach ([
            'Tes IST',
            'Petunjuk IST',
            'Pengerjaan IST',
            'Soal IST',
            'Laporan internal IST',
            'IST Assessment',
            'Intelligenz Struktur Test',
            'Tes Intelegensi Umum',
            '90 Menit',
        ] as $legacyCopy) {
            $this->assertStringNotContainsString($legacyCopy, $source);
        }
    }

    private function source(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$relativePath);

        $this->assertIsString($contents, "Source file could not be read: {$relativePath}");

        return $contents;
    }
}
