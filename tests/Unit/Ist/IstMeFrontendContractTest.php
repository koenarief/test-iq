<?php

namespace Tests\Unit\Ist;

use PHPUnit\Framework\TestCase;

final class IstMeFrontendContractTest extends TestCase
{
    public function test_memorization_renderer_uses_only_five_group_cards(): void
    {
        $source = $this->source('resources/js/Components/IST/IstMemorizationPanel.jsx');

        $this->assertStringContainsString('groups = []', $source);
        $this->assertStringContainsString('Lima kelompok kata untuk dihafalkan', $source);
        $this->assertStringContainsString('grid-cols-1', $source);
        $this->assertStringContainsString('md:grid-cols-2', $source);
        $this->assertStringContainsString('xl:grid-cols-3', $source);
        $this->assertStringContainsString('<ul', $source);
        $this->assertStringContainsString('<li', $source);
        $this->assertStringNotContainsString('pairs', $source);
        $this->assertStringNotContainsString('pair.', $source);
        $this->assertStringNotContainsString('Pasangan kata', $source);
    }

    public function test_work_mounts_only_snapshot_groups_in_memorization_component(): void
    {
        $work = $this->source('resources/js/Pages/IST/Work.jsx');
        $presenter = $this->source('app/Http/Presenters/Ist/IstParticipantPayloadPresenter.php');

        $this->assertStringContainsString('memorizationGroups = []', $work);
        $this->assertStringContainsString('groups={memorizationGroups}', $work);
        $this->assertStringNotContainsString('memorizationPairs', $work.$presenter);
        $this->assertStringContainsString("'memorizationGroups' => \$mode === 'memorization'", $presenter);
        $this->assertStringContainsString("\$meRuntime['groups'] ?? []", $presenter);
        $this->assertStringNotContainsString('memorization_content)', $presenter);
    }

    public function test_me_example_ui_requires_explicit_selection_and_confirmation(): void
    {
        $source = $this->source('resources/js/Pages/IST/Instruction.jsx');

        $this->assertStringContainsString("useForm({ selected_option_key: '' })", $source);
        $this->assertStringContainsString('type="radio"', $source);
        $this->assertStringContainsString('Periksa Contoh dan Lanjut', $source);
        $this->assertStringContainsString('Contoh tidak dihitung sebagai skor dan tidak memulai timer.', $source);
        $this->assertStringContainsString('requiresExampleCompletion && exampleCompleted', $source);
    }

    public function test_staging_me_content_matches_the_category_runtime_contract(): void
    {
        $dataset = json_decode(
            $this->source('database/data/ist-final-staging/me.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $memory = json_decode($dataset['memorization_content'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('5 kelompok', $dataset['instruction_content']);
        $this->assertStringContainsString('2 menit', $dataset['instruction_content']);
        $this->assertStringContainsString('4 menit', $dataset['instruction_content']);
        $this->assertStringContainsString('huruf awal', $dataset['instruction_content']);
        $this->assertStringNotContainsString('pasangan kata', strtolower($dataset['instruction_content']));
        $this->assertSame(['groups'], array_keys($memory));
        $this->assertCount(5, $memory['groups']);
        $this->assertSame([5, 5, 5, 5, 5], array_map(
            static fn (array $group): int => count($group['words']),
            $memory['groups'],
        ));

        $this->assertSame(
            ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'],
            $this->sortedInitials($memory['groups']),
        );

        $categoryNames = array_column($memory['groups'], 'name', 'key');
        $examples = array_values(array_filter(
            $dataset['questions'],
            static fn (array $question): bool => $question['kind'] === 'example',
        ));
        $scored = array_values(array_filter(
            $dataset['questions'],
            static fn (array $question): bool => $question['kind'] === 'scored',
        ));

        $this->assertCount(1, $examples);
        $this->assertCount(12, $scored);
        $this->assertSame(
            'Kata yang mempunyai huruf permulaan P termasuk kelompok …',
            $examples[0]['prompt'],
        );
        $this->assertStringNotContainsString('Pahat', $examples[0]['prompt']);
        $this->assertStringNotContainsString('Pahat', $examples[0]['explanation']);
        $this->assertSame('B', $this->correctKey($examples[0]));

        $expectedKeys = ['A', 'B', 'C', 'E', 'B', 'C', 'D', 'E', 'A', 'D', 'C', 'D'];
        $expectedDifficulty = [
            'easy', 'easy', 'easy', 'easy',
            'medium', 'medium', 'medium', 'medium', 'medium',
            'hard', 'hard', 'hard',
        ];

        foreach ($scored as $index => $question) {
            $internal = $question['metadata']['internal'];
            $this->assertSame(
                "Kata yang mempunyai huruf permulaan {$internal['target_initial']} termasuk kelompok …",
                $question['prompt'],
            );
            $this->assertStringNotContainsString($internal['target_word'], $question['prompt']);
            $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_column($question['options'], 'key'));
            $this->assertSame(array_values($categoryNames), array_column($question['options'], 'text'));
            $this->assertSame($expectedKeys[$index], $this->correctKey($question));
            $this->assertSame($expectedDifficulty[$index], $question['difficulty_target']);
            $this->assertCount(1, array_filter(
                $question['options'],
                static fn (array $option): bool => $option['correct'] === true && $option['score'] === 1,
            ));
            $this->assertCount(4, array_filter(
                $question['options'],
                static fn (array $option): bool => $option['correct'] === false && $option['score'] === 0,
            ));
        }

        $this->assertSame(['easy' => 4, 'medium' => 5, 'hard' => 3], array_count_values(
            array_column($scored, 'difficulty_target'),
        ));
        $this->assertStringNotContainsString('paired_associate', json_encode($dataset, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('main_pairs', json_encode($dataset, JSON_THROW_ON_ERROR));
    }

    private function sortedInitials(array $groups): array
    {
        $initials = [];

        foreach ($groups as $group) {
            foreach ($group['words'] as $word) {
                $initials[] = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
            }
        }

        sort($initials, SORT_STRING);

        return $initials;
    }

    private function correctKey(array $question): string
    {
        $correct = array_values(array_filter(
            $question['options'],
            static fn (array $option): bool => $option['correct'] === true,
        ));

        $this->assertCount(1, $correct);

        return $correct[0]['key'];
    }

    private function source(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$relativePath);

        $this->assertIsString($contents, "Source file could not be read: {$relativePath}");

        return $contents;
    }
}
