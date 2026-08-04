<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstAnswerType;
use PHPUnit\Framework\TestCase;

class IstModelConstantsTest extends TestCase
{
    public function test_answer_types_are_unique_and_complete(): void
    {
        $types = IstAnswerType::all();

        $this->assertCount(4, $types);
        $this->assertSame($types, array_values(array_unique($types)));
    }

    public function test_test_statuses_are_unique(): void
    {
        $statuses = [
            IstTest::STATUS_DRAFT,
            IstTest::STATUS_IN_PROGRESS,
            IstTest::STATUS_COMPLETED,
            IstTest::STATUS_CANCELLED,
        ];

        $this->assertSame($statuses, array_values(array_unique($statuses)));
    }

    public function test_subtest_statuses_and_answer_outcomes_are_unique(): void
    {
        $statuses = [
            IstTestSubtest::STATUS_PENDING,
            IstTestSubtest::STATUS_INSTRUCTION,
            IstTestSubtest::STATUS_MEMORIZING,
            IstTestSubtest::STATUS_ANSWERING,
            IstTestSubtest::STATUS_COMPLETED,
            IstTestSubtest::STATUS_TIMED_OUT,
        ];
        $outcomes = [
            IstAnswer::OUTCOME_CORRECT,
            IstAnswer::OUTCOME_PARTIAL,
            IstAnswer::OUTCOME_WRONG,
            IstAnswer::OUTCOME_BLANK,
        ];

        $this->assertSame($statuses, array_values(array_unique($statuses)));
        $this->assertSame($outcomes, array_values(array_unique($outcomes)));
    }
}
