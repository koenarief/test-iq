<?php

namespace Tests\Unit\Ist;

use App\Support\Ist\IstAnswerType;
use App\Support\Ist\IstSubtestCatalog;
use PHPUnit\Framework\TestCase;

class IstSubtestCatalogTest extends TestCase
{
    public function test_catalog_contains_exactly_nine_subtests_in_the_expected_order(): void
    {
        $catalog = IstSubtestCatalog::all();

        $this->assertCount(IstSubtestCatalog::EXPECTED_SUBTEST_COUNT, $catalog);
        $this->assertSame(
            ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'],
            array_column($catalog, 'code')
        );
        $this->assertSame(range(1, 9), array_column($catalog, 'sequence'));
    }

    public function test_catalog_contains_128_questions_and_3180_seconds_of_core_time(): void
    {
        $catalog = IstSubtestCatalog::all();

        $this->assertSame(
            IstSubtestCatalog::EXPECTED_QUESTION_COUNT,
            array_sum(array_column($catalog, 'question_count'))
        );
        $this->assertSame(
            IstSubtestCatalog::EXPECTED_CORE_DURATION_SECONDS,
            array_sum(array_column($catalog, 'duration_seconds'))
        );
        $this->assertSame(53, array_sum(array_column($catalog, 'duration_seconds')) / 60);
    }

    public function test_every_duration_is_the_sum_of_memorization_and_answering_time(): void
    {
        foreach (IstSubtestCatalog::all() as $subtest) {
            $this->assertSame(
                $subtest['duration_seconds'],
                $subtest['memorization_seconds'] + $subtest['answering_seconds'],
                "Duration mismatch for {$subtest['code']}"
            );
        }
    }

    public function test_me_has_a_two_minute_memorization_and_four_minute_answering_phase(): void
    {
        $me = $this->findSubtest('ME');

        $this->assertSame(120, $me['memorization_seconds']);
        $this->assertSame(240, $me['answering_seconds']);
        $this->assertSame(360, $me['duration_seconds']);

        foreach (IstSubtestCatalog::all() as $subtest) {
            if ($subtest['code'] !== 'ME') {
                $this->assertSame(0, $subtest['memorization_seconds']);
            }
        }
    }

    public function test_catalog_uses_the_final_answer_type_for_each_subtest(): void
    {
        $typesByCode = array_column(
            IstSubtestCatalog::all(),
            'default_answer_type',
            'code'
        );

        $this->assertSame([
            'SE' => IstAnswerType::SINGLE_CHOICE,
            'WA' => IstAnswerType::SINGLE_CHOICE,
            'AN' => IstAnswerType::SINGLE_CHOICE,
            'GE' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'RA' => IstAnswerType::NUMERIC,
            'ZR' => IstAnswerType::NUMERIC,
            'FA' => IstAnswerType::IMAGE_CHOICE,
            'WU' => IstAnswerType::IMAGE_CHOICE,
            'ME' => IstAnswerType::SINGLE_CHOICE,
        ], $typesByCode);
    }

    private function findSubtest(string $code): array
    {
        foreach (IstSubtestCatalog::all() as $subtest) {
            if ($subtest['code'] === $code) {
                return $subtest;
            }
        }

        $this->fail("Subtest {$code} was not found in the catalog.");
    }
}
