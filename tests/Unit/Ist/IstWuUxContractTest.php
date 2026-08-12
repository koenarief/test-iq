<?php

namespace Tests\Unit\Ist;

use PHPUnit\Framework\TestCase;

final class IstWuUxContractTest extends TestCase
{
    private string $root;

    private array $wu;

    private array $media;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = dirname(__DIR__, 3);
        $this->wu = $this->json('database/data/ist-final-staging/wu.json');
        $metadata = $this->json('database/data/ist-final-staging/media/metadata.json');
        $this->media = collect($metadata['media'])->keyBy('logical_id')->all();
    }

    public function test_each_wu_question_has_one_target_and_the_same_five_master_cubes(): void
    {
        $questions = $this->wu['questions'];

        $this->assertCount(13, $questions);
        $this->assertStringContainsString(
            'Pada setiap soal hanya ditampilkan satu kubus.',
            $this->wu['instruction_content']
        );
        $this->assertStringNotContainsString(
            'dua tampilan kubus',
            strtolower($this->wu['instruction_content'])
        );

        $masterHashes = array_fill_keys(['A', 'B', 'C', 'D', 'E'], []);

        foreach ($questions as $question) {
            $this->assertCount(5, $question['options']);
            $this->assertSame(
                ['A', 'B', 'C', 'D', 'E'],
                array_column($question['options'], 'key')
            );

            $promptMedia = $this->media[$question['media']['prompt_ref']];
            $targetSvg = $this->source(
                'database/data/ist-final-staging/'.$promptMedia['relative_path']
            );

            $this->assertStringContainsString('width="240"', $targetSvg);
            $this->assertStringContainsString('viewBox="0 0 240 180"', $targetSvg);
            $this->assertStringNotContainsString('width="480"', $targetSvg);

            foreach ($question['options'] as $option) {
                $optionMedia = $this->media[$option['media_ref']];
                $optionPath = $this->root.'/database/data/ist-final-staging/'.$optionMedia['relative_path'];
                $masterHashes[$option['key']][] = hash_file('sha256', $optionPath);
            }
        }

        foreach ($masterHashes as $key => $hashes) {
            $this->assertCount(
                1,
                array_unique($hashes),
                "Visual master cube {$key} must be identical for every WU question."
            );
        }
    }

    public function test_every_wu_target_matches_exactly_one_non_mirrored_master_across_24_rotations(): void
    {
        $example = collect($this->wu['questions'])->firstWhere('kind', 'example');
        $masters = [];
        $rotationKeys = [];
        $reflectionKeys = [];

        foreach ($example['options'] as $option) {
            $media = $this->media[$option['media_ref']];
            $svg = $this->source('database/data/ist-final-staging/'.$media['relative_path']);
            $this->assertStringContainsString('data-wu-role="master"', $svg);
            $masters[$option['key']] = $this->parseOrientation($svg);
            $rotations = $this->rotations($masters[$option['key']]);
            $reflections = $this->rotations($this->reflect($masters[$option['key']]));

            $this->assertCount(24, $rotations, "Master {$option['key']} tidak memiliki 24 proper rotations.");
            $this->assertCount(24, $reflections);
            $rotationKeys[$option['key']] = array_fill_keys(array_map([$this, 'orientationKey'], $rotations), true);
            $reflectionKeys[$option['key']] = array_fill_keys(array_map([$this, 'orientationKey'], $reflections), true);
            $this->assertSame([], array_intersect_key($rotationKeys[$option['key']], $reflectionKeys[$option['key']]));
        }

        $visibleMasterSets = [];

        foreach ($masters as $key => $master) {
            $visible = [$master['U'], $master['F'], $master['R']];
            sort($visible, SORT_STRING);
            $visibleKey = implode('|', $visible);
            $this->assertArrayNotHasKey($visibleKey, $visibleMasterSets, "Master {$key} tidak berbeda secara visual.");
            $visibleMasterSets[$visibleKey] = $key;
        }

        $this->assertCount(5, $visibleMasterSets);

        foreach (array_keys($masters) as $leftIndex => $leftKey) {
            foreach (array_slice(array_keys($masters), $leftIndex + 1) as $rightKey) {
                $this->assertSame(
                    [],
                    array_intersect_key($rotationKeys[$leftKey], $rotationKeys[$rightKey]),
                    "Master {$leftKey} dan {$rightKey} ekuivalen melalui rotasi."
                );
                $this->assertSame(
                    [],
                    array_intersect_key($reflectionKeys[$leftKey], $rotationKeys[$rightKey]),
                    "Master {$leftKey} merupakan mirror-equivalent dari {$rightKey}."
                );
            }
        }

        $scoredTriples = [];

        foreach ($this->wu['questions'] as $question) {
            $promptMedia = $this->media[$question['media']['prompt_ref']];
            $targetSvg = $this->source('database/data/ist-final-staging/'.$promptMedia['relative_path']);
            $target = $this->parseOrientation($targetSvg);
            $targetKey = $this->orientationKey($target);
            $visibleTriple = implode('|', [$target['U'], $target['F'], $target['R']]);
            $correctOptions = collect($question['options'])->where('correct', true);
            $expectedKey = $correctOptions->first()['key'];
            $matches = array_keys(array_filter(
                $rotationKeys,
                static fn (array $keys): bool => isset($keys[$targetKey])
            ));

            $this->assertStringContainsString('data-wu-role="target"', $targetSvg);
            $this->assertCount(1, $correctOptions, "{$question['logical_id']} mempunyai multi/zero-correct option.");
            $this->assertSame([$expectedKey], $matches, "{$question['logical_id']} ambigu pada 24 proper rotations.");

            $targetVisible = [$target['U'], $target['F'], $target['R']];
            $referenceVisible = [$masters[$expectedKey]['U'], $masters[$expectedKey]['F'], $masters[$expectedKey]['R']];
            sort($targetVisible, SORT_STRING);
            sort($referenceVisible, SORT_STRING);
            $this->assertSame(
                $referenceVisible,
                $targetVisible,
                "{$question['logical_id']} tidak dapat diselesaikan dari tiga simbol yang terlihat."
            );
            $visualMatches = array_keys(array_filter(
                $masters,
                static function (array $master) use ($targetVisible): bool {
                    $visible = [$master['U'], $master['F'], $master['R']];
                    sort($visible, SORT_STRING);

                    return $visible === $targetVisible;
                }
            ));
            $this->assertSame([$expectedKey], $visualMatches, "{$question['logical_id']} ambigu dari visual peserta.");

            if ($question['kind'] === 'scored') {
                $this->assertArrayNotHasKey($visibleTriple, $scoredTriples, "Visible triple duplikat: {$visibleTriple}.");
                $scoredTriples[$visibleTriple] = true;
            }
        }

        $this->assertCount(12, $scoredTriples);
    }

    public function test_wu_keys_and_difficulties_remain_equal_to_the_human_reviewed_draft(): void
    {
        $draft = $this->json('docs/ist/content-drafts/stage-15-assets/geometry-spec-draft.json');
        $draftRecords = collect($draft['records'])
            ->where('subtest_code', 'WU')
            ->keyBy('logical_id');

        foreach ($this->wu['questions'] as $question) {
            $draftId = $question['kind'] === 'example'
                ? 'wu-example-001'
                : 'wu-'.str_pad((string) $question['display_order'], 3, '0', STR_PAD_LEFT);
            $draftRecord = $draftRecords[$draftId];
            $actualKey = collect($question['options'])->firstWhere('correct', true)['key'];
            $draftKey = collect($draftRecord['options'])->firstWhere('is_correct', true)['code'];

            $this->assertSame($draftKey, $actualKey, "Answer key changed for {$question['logical_id']}.");
            $this->assertSame(
                $draftRecord['difficulty_target'],
                $question['difficulty_target'],
                "Difficulty changed for {$question['logical_id']}."
            );
        }
    }

    public function test_wu_frontend_renders_visual_masters_in_instruction_and_work(): void
    {
        $questionCard = $this->source('resources/js/Components/IST/IstQuestionCard.jsx');
        $instruction = $this->source('resources/js/Pages/IST/Instruction.jsx');
        $work = $this->source('resources/js/Pages/IST/Work.jsx');
        $visuals = $this->source('resources/js/Support/IST/wuVisuals.js');

        $this->assertStringContainsString('Kubus acuan ${optionKey}', $questionCard);
        $this->assertStringContainsString('Pilih kubus acuan A–E', $questionCard);
        $this->assertStringContainsString(
            'grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5',
            $questionCard
        );
        $this->assertMatchesRegularExpression(
            '/isWu\s*\?\s*wuMasterImage\(optionKey\)\s*:\s*option\?\.image/',
            $questionCard,
        );
        $this->assertStringContainsString('Kubus acuan A–E', $instruction);
        $this->assertStringContainsString('{!isWu && (', $instruction);
        $this->assertStringContainsString('subtestCode={subtest?.code}', $work);
        $this->assertStringContainsString('wuQuestionTargetImage(question.displayOrder)', $questionCard);
        $this->assertStringContainsString('wuMasterImage(optionKey)', $questionCard);
        $this->assertStringContainsString('wuExampleTargetImage()', $instruction);
        $this->assertStringContainsString('WU_INSTRUCTION_CONTENT', $instruction);
        $this->assertStringContainsString('Pada setiap soal hanya ditampilkan satu kubus target', $visuals);
        $this->assertStringContainsString('Pilih tepat satu kubus acuan', $visuals);
        $this->assertSame(12, substr_count($visuals, "import target0"));
        $this->assertStringNotContainsString('dua tampilan kubus', strtolower($visuals));
        $this->assertStringNotContainsString('dua kubus', strtolower($visuals));
    }

    private function json(string $relativePath): array
    {
        return json_decode(
            $this->source($relativePath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function source(string $relativePath): string
    {
        $contents = file_get_contents($this->root.'/'.$relativePath);

        $this->assertIsString($contents, "Source file could not be read: {$relativePath}");

        return $contents;
    }

    private function parseOrientation(string $svg): array
    {
        $orientation = [];

        foreach (['U', 'D', 'F', 'B', 'L', 'R'] as $position) {
            $this->assertSame(
                1,
                preg_match('/data-face-'.strtolower($position).'="([a-z]+)"/', $svg, $matches),
                "Atribut face {$position} tidak tersedia."
            );
            $orientation[$position] = $matches[1];
        }

        return $orientation;
    }

    public function orientationKey(array $orientation): string
    {
        return implode('|', array_map(
            static fn (string $position): string => $orientation[$position],
            ['U', 'D', 'F', 'B', 'L', 'R']
        ));
    }

    private function rotateX(array $orientation): array
    {
        return [
            'U' => $orientation['B'], 'D' => $orientation['F'],
            'F' => $orientation['U'], 'B' => $orientation['D'],
            'L' => $orientation['L'], 'R' => $orientation['R'],
        ];
    }

    private function rotateY(array $orientation): array
    {
        return [
            'U' => $orientation['U'], 'D' => $orientation['D'],
            'F' => $orientation['L'], 'B' => $orientation['R'],
            'L' => $orientation['B'], 'R' => $orientation['F'],
        ];
    }

    private function rotateZ(array $orientation): array
    {
        return [
            'U' => $orientation['R'], 'D' => $orientation['L'],
            'F' => $orientation['F'], 'B' => $orientation['B'],
            'L' => $orientation['U'], 'R' => $orientation['D'],
        ];
    }

    private function reflect(array $orientation): array
    {
        return [
            ...$orientation,
            'L' => $orientation['R'],
            'R' => $orientation['L'],
        ];
    }

    private function rotations(array $orientation): array
    {
        $queue = [$orientation];
        $rotations = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $key = $this->orientationKey($current);

            if (isset($rotations[$key])) {
                continue;
            }

            $rotations[$key] = $current;
            $queue[] = $this->rotateX($current);
            $queue[] = $this->rotateY($current);
            $queue[] = $this->rotateZ($current);
        }

        return array_values($rotations);
    }
}
