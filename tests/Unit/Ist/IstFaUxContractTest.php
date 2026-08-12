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
        preg_match_all('/<rect\s+([^>]+)\/>/', $svg, $matches);
        $coordinates = [];

        foreach ($matches[1] as $attributes) {
            $values = [];

            foreach (['x', 'y', 'width', 'height'] as $name) {
                $this->assertSame(
                    1,
                    preg_match('/(?:^|\s)'.preg_quote($name, '/').'="(-?[0-9]+)"/', $attributes, $match),
                    "Atribut {$name} tidak tersedia."
                );
                $values[] = $match[1];
            }

            $coordinates[] = implode(',', $values);
        }

        $this->assertNotEmpty($coordinates);
        sort($coordinates, SORT_STRING);

        return hash('sha256', implode(';', $coordinates));
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
