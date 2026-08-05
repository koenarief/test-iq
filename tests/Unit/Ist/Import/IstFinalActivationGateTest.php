<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Services\Ist\Import\Final\IstFinalActivationGate;
use PHPUnit\Framework\TestCase;

final class IstFinalActivationGateTest extends TestCase
{
    public function test_test_fixture_can_never_be_activated(): void
    {
        $this->expectException(InvalidIstFinalQuestionDatasetException::class);

        (new IstFinalActivationGate)->assertReady(
            ['status' => 'frozen', 'active' => false, 'test_fixture' => true],
            array_fill_keys([
                'import_succeeded',
                'approval_complete',
                'checksums_valid',
                'golden_tests_passed',
                'uat_signed_off',
                'explicit_activation_approved',
            ], true),
        );
    }

    public function test_every_release_evidence_flag_is_required(): void
    {
        $this->expectException(InvalidIstFinalQuestionDatasetException::class);

        (new IstFinalActivationGate)->assertReady(
            ['status' => 'frozen', 'active' => false, 'test_fixture' => false],
            [
                'import_succeeded' => true,
                'approval_complete' => true,
                'checksums_valid' => true,
                'golden_tests_passed' => true,
                'uat_signed_off' => false,
                'explicit_activation_approved' => true,
            ],
        );
    }
}
