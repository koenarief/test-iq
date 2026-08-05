<?php

namespace Tests\Feature\Ist\Import;

use Tests\Feature\Ist\Http\IstHttpTestCase;

final class IstFinalParticipantPayloadSecurityTest extends IstHttpTestCase
{
    public function test_participant_work_payload_does_not_expose_scoring_or_internal_metadata(): void
    {
        [$test] = $this->createOwnedTest('Final payload security');
        [$runtime] = $this->activateFirstRuntime($test);
        $url = route('ist.subtests.work', [
            'test' => $test->public_id,
            'subtest' => $runtime->subtest->code,
        ]);

        $response = $this->withHeader('X-Inertia', 'true')->get($url)->assertOk();
        $content = $response->getContent();

        foreach ([
            'answer_key_snapshot',
            'correct_option_key',
            'score_value',
            'is_correct',
            'approval_version',
            'owner_approver',
            'provenance',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $content);
        }
    }
}
