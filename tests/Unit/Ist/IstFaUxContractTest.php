<?php

namespace Tests\Unit\Ist;

use PHPUnit\Framework\TestCase;

final class IstFaUxContractTest extends TestCase
{
    private string $root;

    private array $fa;

    private array $media;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = dirname(__DIR__, 3);
        $this->fa = $this->json('database/data/ist-final-staging/fa.json');
        $metadata = $this->json('database/data/ist-final-staging/media/metadata.json');
        $this->media = collect($metadata['media'])->keyBy('logical_id')->all();
    }

    public function test_every_option_preserves_its_human_reviewed_per_item_geometry(): void
    {
        $this->assertCount(11, $this->fa['questions']);
        $optionCount = 0;

        foreach ($this->fa['questions'] as $question) {
            $this->assertCount(5, $question['options']);
            $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_column($question['options'], 'key'));

            foreach ($question['options'] as $option) {
                $record = $this->media[$option['media_ref']];
                $relative = substr($record['relative_path'], strlen('media/fa/'));
                $baseline = $this->source('docs/ist/content-drafts/stage-15-assets/fa/'.$relative);
                $styled = $this->source('database/data/ist-final-staging/'.$record['relative_path']);

                $this->assertSame(
                    $this->geometryFingerprint($baseline),
                    $this->geometryFingerprint($styled),
                    "Geometri baseline berubah untuk {$question['logical_id']} opsi {$option['key']}."
                );
                $this->assertStringContainsString('fill="#e5e7eb"', $styled);
                $this->assertStringContainsString('stroke="#374151" stroke-width="2"', $styled);
                $this->assertStringContainsString('data-fa-layer="outer-outline"', $styled);
                $this->assertStringContainsString('stroke="#111827" stroke-width="3"', $styled);
                $this->assertStringContainsString('vector-effect="non-scaling-stroke"', $styled);
                $this->assertStringContainsString('shape-rendering="crispEdges"', $styled);
                $optionCount++;
            }
        }

        $this->assertSame(55, $optionCount);
    }

    public function test_keys_difficulties_and_geometry_review_contract_remain_unchanged(): void
    {
        $draft = $this->json('docs/ist/content-drafts/stage-15-assets/geometry-spec-draft.json');
        $draftRecords = collect($draft['records'])
            ->where('subtest_code', 'FA')
            ->keyBy('logical_id');
        $difficultyCounts = ['easy' => 0, 'medium' => 0, 'hard' => 0];

        foreach ($this->fa['questions'] as $question) {
            $draftId = $question['kind'] === 'example'
                ? 'fa-example-001'
                : 'fa-'.str_pad((string) $question['display_order'], 3, '0', STR_PAD_LEFT);
            $draftRecord = $draftRecords[$draftId];
            $datasetCorrect = collect($question['options'])->where('correct', true);
            $reviewCorrect = collect($draftRecord['options'])->where('is_correct', true);
            $exactCover = collect($draftRecord['options'])->where('failure_mode', 'exact_cover_valid');

            $this->assertCount(1, $datasetCorrect, "{$question['logical_id']} harus mempunyai satu key dataset.");
            $this->assertCount(1, $reviewCorrect, "{$draftId} harus mempunyai satu key review.");
            $this->assertCount(1, $exactCover, "{$draftId} harus mempunyai satu exact-cover valid.");
            $this->assertSame($reviewCorrect->first()['code'], $datasetCorrect->first()['key']);
            $this->assertSame($exactCover->first()['code'], $datasetCorrect->first()['key']);
            $this->assertSame('human_review_passed', $draftRecord['review_status']);
            $this->assertSame($draftRecord['difficulty_target'], $question['difficulty_target']);

            if ($question['kind'] === 'scored') {
                $difficultyCounts[$question['difficulty_target']]++;
            }
        }

        $this->assertSame(['easy' => 3, 'medium' => 4, 'hard' => 3], $difficultyCounts);
    }

    public function test_each_prompt_matches_the_reviewed_baseline_and_has_one_exact_cover_answer(): void
    {
        $draft = $this->json('docs/ist/content-drafts/stage-15-assets/geometry-spec-draft.json');
        $draftRecords = collect($draft['records'])
            ->where('subtest_code', 'FA')
            ->keyBy('logical_id');
        $verifiedKeys = [];

        foreach ($this->fa['questions'] as $question) {
            $draftId = $question['kind'] === 'example'
                ? 'fa-example-001'
                : 'fa-'.str_pad((string) $question['display_order'], 3, '0', STR_PAD_LEFT);
            $draftRecord = $draftRecords[$draftId];
            $promptRecord = $this->media[$question['media']['prompt_ref']];
            $relative = substr($promptRecord['relative_path'], strlen('media/fa/'));
            $baseline = $this->source('docs/ist/content-drafts/stage-15-assets/fa/'.$relative);
            $staging = $this->source('database/data/ist-final-staging/'.$promptRecord['relative_path']);

            $this->assertSame(
                $this->geometryFingerprint($baseline),
                $this->geometryFingerprint($staging),
                "Geometri prompt {$draftId} berbeda dari baseline human-reviewed."
            );
            $this->assertSame(
                array_sum(array_map('count', $draftRecord['pieces'])),
                count($this->rectangles($staging)),
                "Jumlah unit potongan {$draftId} tidak sesuai dengan spesifikasi."
            );

            $tileable = [];
            foreach ($draftRecord['options'] as $option) {
                if ($this->canTile($option['cells'], $draftRecord['pieces'])) {
                    $tileable[] = $option['code'];
                }
            }

            $datasetKey = collect($question['options'])->firstWhere('correct', true)['key'];
            $this->assertSame([$datasetKey], $tileable, "{$draftId} harus mempunyai tepat satu exact-cover yang cocok dengan key.");
            $verifiedKeys[] = $datasetKey;
        }

        $this->assertSame(['B', 'C', 'A', 'E', 'B', 'D', 'C', 'A', 'E', 'B', 'D'], $verifiedKeys);
    }

    public function test_frontend_resolves_fa_options_per_item_and_keeps_prompt_from_snapshot(): void
    {
        $questionCard = $this->source('resources/js/Components/IST/IstQuestionCard.jsx');
        $instruction = $this->source('resources/js/Pages/IST/Instruction.jsx');
        $visuals = $this->source('resources/js/Support/IST/faVisuals.js');

        $this->assertStringContainsString('faQuestionOptionImage(question.displayOrder, optionKey)', $questionCard);
        $this->assertStringContainsString(': question.image;', $questionCard);
        $this->assertStringContainsString('faExampleOptionImage(optionKey)', $instruction);
        $this->assertStringContainsString('media/fa/options/*.svg', $visuals);
        $this->assertStringContainsString('fa-q${order}-option-${key}.svg', $visuals);
        $this->assertStringNotContainsString('faMasterImage', $questionCard.$instruction.$visuals);
    }

    public function test_fa_prompt_media_is_not_rewritten_as_a_styled_option(): void
    {
        $promptCount = 0;

        foreach ($this->fa['questions'] as $question) {
            $record = $this->media[$question['media']['prompt_ref']];
            $prompt = $this->source('database/data/ist-final-staging/'.$record['relative_path']);

            $this->assertStringNotContainsString('data-fa-layer="outer-outline"', $prompt);
            $promptCount++;
        }

        $this->assertSame(11, $promptCount);
    }

    private function geometryFingerprint(string $svg): string
    {
        $coordinates = [];

        foreach ($this->rectangles($svg) as $rectangle) {
            $coordinates[] = implode(',', $rectangle);
        }

        $this->assertNotEmpty($coordinates);
        sort($coordinates, SORT_STRING);

        return hash('sha256', implode(';', $coordinates));
    }

    /** @return list<array{x:int,y:int,width:int,height:int}> */
    private function rectangles(string $svg): array
    {
        preg_match_all('/<rect\s+([^>]+)\/>/', $svg, $matches);
        $rectangles = [];

        foreach ($matches[1] as $attributes) {
            $values = [];

            foreach (['x', 'y', 'width', 'height'] as $name) {
                $this->assertSame(
                    1,
                    preg_match('/(?:^|\s)'.preg_quote($name, '/').'="(-?[0-9]+)"/', $attributes, $match),
                    "Atribut {$name} tidak tersedia."
                );
                $values[$name] = (int) $match[1];
            }

            $rectangles[] = $values;
        }

        return $rectangles;
    }

    private function canTile(array $target, array $pieces): bool
    {
        if (count($target) !== array_sum(array_map('count', $pieces))) {
            return false;
        }

        $remaining = [];
        foreach ($target as $cell) {
            $remaining[$this->cellKey($cell)] = [(int) $cell[0], (int) $cell[1]];
        }

        if (count($remaining) !== count($target)) {
            return false;
        }

        $pieceOrientations = array_map(fn (array $piece): array => $this->orientations($piece), $pieces);
        $memo = [];

        $search = function (array $available, int $usedMask) use (&$search, &$memo, $pieceOrientations, $pieces): bool {
            if ($available === []) {
                return $usedMask === (1 << count($pieces)) - 1;
            }

            ksort($available);
            $memoKey = $usedMask.'|'.implode(';', array_keys($available));

            if (array_key_exists($memoKey, $memo)) {
                return $memo[$memoKey];
            }

            $anchor = reset($available);

            foreach ($pieceOrientations as $pieceIndex => $variants) {
                if (($usedMask & (1 << $pieceIndex)) !== 0) {
                    continue;
                }

                foreach ($variants as $variant) {
                    foreach ($variant as $origin) {
                        $offsetX = $anchor[0] - $origin[0];
                        $offsetY = $anchor[1] - $origin[1];
                        $placed = [];

                        foreach ($variant as $cell) {
                            $key = $this->cellKey([$cell[0] + $offsetX, $cell[1] + $offsetY]);

                            if (! isset($available[$key])) {
                                continue 2;
                            }

                            $placed[] = $key;
                        }

                        $next = $available;
                        foreach ($placed as $key) {
                            unset($next[$key]);
                        }

                        if ($search($next, $usedMask | (1 << $pieceIndex))) {
                            return $memo[$memoKey] = true;
                        }
                    }
                }
            }

            return $memo[$memoKey] = false;
        };

        return $search($remaining, 0);
    }

    private function orientations(array $piece): array
    {
        $orientations = [];

        for ($rotation = 0; $rotation < 4; $rotation++) {
            $transformed = [];

            foreach ($piece as $cell) {
                $x = (int) $cell[0];
                $y = (int) $cell[1];

                for ($step = 0; $step < $rotation; $step++) {
                    [$x, $y] = [-$y, $x];
                }

                $transformed[] = [$x, $y];
            }

            $normalized = $this->normalizeCells($transformed);
            $orientations[$this->cellSetKey($normalized)] = $normalized;
        }

        return array_values($orientations);
    }

    private function normalizeCells(array $cells): array
    {
        $minimumX = min(array_column($cells, 0));
        $minimumY = min(array_column($cells, 1));
        $normalized = array_map(
            static fn (array $cell): array => [(int) $cell[0] - $minimumX, (int) $cell[1] - $minimumY],
            $cells,
        );
        usort($normalized, static fn (array $left, array $right): int => [$left[1], $left[0]] <=> [$right[1], $right[0]]);

        return $normalized;
    }

    private function cellSetKey(array $cells): string
    {
        return implode(';', array_map(fn (array $cell): string => $this->cellKey($cell), $this->normalizeCells($cells)));
    }

    private function cellKey(array $cell): string
    {
        return ((int) $cell[0]).','.((int) $cell[1]);
    }

    private function json(string $relativePath): array
    {
        return json_decode($this->source($relativePath), true, 512, JSON_THROW_ON_ERROR);
    }

    private function source(string $relativePath): string
    {
        $contents = file_get_contents($this->root.'/'.$relativePath);

        $this->assertIsString($contents, "Source file could not be read: {$relativePath}");

        return $contents;
    }
}
