<?php

namespace Tests\Unit\Ist;

use App\Models\Ist\IstSubtest;
use App\Support\Ist\IstMeRuntimeContent;
use DomainException;
use PHPUnit\Framework\TestCase;

final class IstMeRuntimeContentTest extends TestCase
{
    private IstMeRuntimeContent $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content = new IstMeRuntimeContent();
    }

    public function test_it_builds_exactly_five_safe_groups_with_25_unique_initials(): void
    {
        $snapshot = $this->content->fromMaster($this->master());

        $this->assertSame(IstMeRuntimeContent::MODEL, $snapshot['model']);
        $this->assertStringNotContainsString('pasangan', strtolower($snapshot['instructionContent']));
        $this->assertCount(5, $snapshot['groups']);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_column($snapshot['groups'], 'key'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($snapshot['groups'], 'displayOrder'));
        $this->assertSame(25, array_sum(array_map(
            static fn (array $group): int => count($group['words']),
            $snapshot['groups'],
        )));
        $this->assertArrayNotHasKey('pairs', $snapshot);
        $this->assertArrayNotHasKey('target_word', $snapshot);
        $this->assertSame(
            $snapshot,
            $this->content->fromQuestionSnapshot(['me_runtime' => $snapshot]),
        );
        $this->assertNull($this->content->fromQuestionSnapshot([
            'me_runtime' => ['pairs' => []],
        ]));
    }

    public function test_it_rejects_legacy_pair_content_for_a_new_session(): void
    {
        $master = $this->master();
        $master->memorization_content = json_encode([
            'pairs' => [['cue' => 'satu', 'associate' => 'dua']],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(DomainException::class);
        $this->content->fromMaster($master);
    }

    public function test_it_rejects_instruction_using_the_old_pair_mechanism(): void
    {
        $master = $this->master();
        $master->instruction_content = 'Hafalkan pasangan kata berikut.';

        $this->expectException(DomainException::class);
        $this->content->fromMaster($master);
    }

    public function test_it_rejects_duplicate_initials(): void
    {
        $master = $this->master();
        $memory = json_decode($master->memorization_content, true, 512, JSON_THROW_ON_ERROR);
        $memory['groups'][1]['words'][0] = $memory['groups'][0]['words'][0].' lain';
        $master->memorization_content = json_encode($memory, JSON_THROW_ON_ERROR);

        $this->expectException(DomainException::class);
        $this->content->fromMaster($master);
    }

    public function test_it_rejects_a_group_without_five_words(): void
    {
        $master = $this->master();
        $memory = json_decode($master->memorization_content, true, 512, JSON_THROW_ON_ERROR);
        array_pop($memory['groups'][0]['words']);
        $master->memorization_content = json_encode($memory, JSON_THROW_ON_ERROR);

        $this->expectException(DomainException::class);
        $this->content->fromMaster($master);
    }

    public function test_it_rejects_noncanonical_me_timers(): void
    {
        $master = $this->master();
        $master->answering_seconds = 241;

        $this->expectException(DomainException::class);
        $this->content->fromMaster($master);
    }

    private function master(): IstSubtest
    {
        $initials = range('A', 'Y');
        $groups = [];

        foreach (['A', 'B', 'C', 'D', 'E'] as $groupIndex => $key) {
            $groups[] = [
                'key' => $key,
                'name' => "Kategori {$key}",
                'words' => array_map(
                    static fn (string $initial): string => $initial.'kata',
                    array_slice($initials, $groupIndex * 5, 5),
                ),
                'display_order' => $groupIndex + 1,
            ];
        }

        return new IstSubtest([
            'code' => 'ME',
            'instruction_content' => 'Hafalkan lima kelompok. Setelah itu pilih kategori berdasarkan huruf awal.',
            'memorization_content' => json_encode(['groups' => $groups], JSON_THROW_ON_ERROR),
            'memorization_seconds' => 120,
            'answering_seconds' => 240,
        ]);
    }
}
