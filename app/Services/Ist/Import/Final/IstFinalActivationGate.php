<?php

namespace App\Services\Ist\Import\Final;

use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;

final class IstFinalActivationGate
{
    /**
     * Contract-only gate. This service never updates questions, routes, or the
     * landing card; a future explicit activation action must call it first.
     */
    public function assertReady(array $manifest, array $evidence): void
    {
        if (($manifest['status'] ?? null) !== 'frozen' || ($manifest['active'] ?? null) !== false) {
            $this->fail('dataset harus frozen dan masih inactive sebelum aktivasi');
        }

        if (($manifest['test_fixture'] ?? false) === true) {
            $this->fail('test fixture tidak pernah boleh diaktifkan');
        }

        foreach ([
            'import_succeeded',
            'approval_complete',
            'checksums_valid',
            'golden_tests_passed',
            'uat_signed_off',
            'explicit_activation_approved',
        ] as $gate) {
            if (($evidence[$gate] ?? null) !== true) {
                $this->fail("activation gate {$gate} belum terpenuhi");
            }
        }
    }

    private function fail(string $reason): never
    {
        throw InvalidIstFinalQuestionDatasetException::because($reason);
    }
}
