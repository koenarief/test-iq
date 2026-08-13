<?php

namespace Tests\Unit\Ist;

use PHPUnit\Framework\TestCase;
use Tests\Support\Ist\WuSourceGeometry;

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
            'Pada setiap soal hanya ditampilkan satu kubus target',
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
        $expected = [
            137 => 'A', 138 => 'C', 139 => 'D', 140 => 'E',
            141 => 'A', 142 => 'C', 143 => 'D', 144 => 'C',
            145 => 'E', 146 => 'A', 147 => 'B', 148 => 'D',
        ];
        $audit = WuSourceGeometry::audit();

        $this->assertCount(12, $audit);

        foreach ($expected as $source => $key) {
            $this->assertSame(24, $audit[$source]['proper_rotations_checked']);
            $this->assertSame(24, $audit[$source]['reflections_checked']);
            $this->assertSame(
                [$key => 1],
                $audit[$source]['proper_matches'],
                "WU sumber {$source} harus mempunyai tepat satu proper-rotation match."
            );
            $this->assertSame(
                [],
                $audit[$source]['reflection_matches'],
                "WU sumber {$source} tidak boleh cocok melalui reflection."
            );
        }
    }

    public function test_wu_keys_difficulties_and_source_media_are_final(): void
    {
        $expectedKeys = ['A', 'C', 'D', 'E', 'A', 'C', 'D', 'C', 'E', 'A', 'B', 'D'];
        $expectedDifficulty = [
            'easy', 'easy', 'easy', 'easy',
            'medium', 'medium', 'medium', 'medium', 'medium',
            'hard', 'hard', 'hard',
        ];
        $expectedHashes = [
            '2948fac56a6a50a36138692464cafff2011e04a5b31f9927eda908207f0ba1ba',
            '99b60f5e6d9ee21a40377ff6cd6275521a2ec1bbf220e5515c6bd87cc42d3479',
            'f501ea2667ca7cfdacdf4a654c0143f309c19f18528588bd7d738286f68a1a6e',
            'bc0ea10e5f75282097c8ef92e73896eff4f6a77e65da2b0c6459aab557bf50bf',
            'da69079dcc0feb5384f6bb262974af6fd5f932727f08a16a9d28af7a152ea103',
            '7480b5086f3d02bd70df5f173d7bcbccd7e9e4994aefc439a8ff363b6dec763c',
            'd5eb7f8e30b3bea6886633ed3223605a782a14416f84f299985013b37e443f0e',
            'cba591de8202b952e730625800ba37285dfa782d70e1d9383acb98b14dee4440',
            '1b7507171e2fb723677844f430303def08c0e651fc880e8e9c1cb9dc8da71998',
            'd5295162292e8f590838f519dedb064e5d0ff9ef932d0214ed58f2f6ee2fd582',
            '4f50801bb773c1be2e807682b9ed580b4501a96e94e5052988783ecd6a38942c',
            '61e2aaae2182402b0ce9b97232d2c5abf0752d4de6a0d25e53d9789085efb9c5',
        ];
        $scored = array_values(array_filter(
            $this->wu['questions'],
            static fn (array $question): bool => $question['kind'] === 'scored',
        ));

        $this->assertSame('A', collect($this->wu['questions'][0]['options'])->firstWhere('correct', true)['key']);

        foreach ($scored as $index => $question) {
            $actualKey = collect($question['options'])->firstWhere('correct', true)['key'];
            $prompt = $this->media[$question['media']['prompt_ref']];
            $path = $this->root.'/database/data/ist-final-staging/'.$prompt['relative_path'];

            $this->assertSame($expectedKeys[$index], $actualKey);
            $this->assertSame($expectedDifficulty[$index], $question['difficulty_target']);
            $this->assertSame(137 + $index, $question['metadata']['internal']['source_item_number']);
            $this->assertSame($expectedHashes[$index], hash_file('sha256', $path));
        }

        $this->assertSame(
            ['easy' => 4, 'medium' => 5, 'hard' => 3],
            array_count_values(array_column($scored, 'difficulty_target')),
        );
        $this->assertSame(23, array_sum(array_map(
            static fn (array $question): int => ['easy' => 1, 'medium' => 2, 'hard' => 3][$question['difficulty_target']],
            $scored,
        )));
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
