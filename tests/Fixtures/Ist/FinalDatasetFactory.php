<?php

namespace Tests\Fixtures\Ist;

use App\Support\Ist\IstAnswerType;
use App\Support\Ist\IstSubtestCatalog;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FinalDatasetFactory
{
    public const QUESTION_BANK_VERSION = '1.0.0-test';

    public const RECORD_VERSION = 100000001;

    public static function create(): string
    {
        $directory = sys_get_temp_dir().'/ist-final-fixture-'.bin2hex(random_bytes(8));
        mkdir($directory.'/media/options', 0750, true);
        $media = self::createMedia($directory);
        $subtestEntries = [];

        foreach (IstSubtestCatalog::all() as $definition) {
            $code = $definition['code'];
            $file = strtolower($code).'.json';
            $subtestEntries[] = [
                'code' => $code,
                'file' => $file,
                'scored_question_count' => $definition['question_count'],
                'example_count' => 1,
                'duration_seconds' => $definition['duration_seconds'],
                'memorization_duration_seconds' => $definition['memorization_seconds'],
                'answering_duration_seconds' => $definition['answering_seconds'],
            ];
            self::writeJson($directory.'/'.$file, self::subtestPayload($definition));
        }

        $manifest = [
            'schema_version' => 1,
            'dataset_type' => 'final',
            'test_fixture' => true,
            'instrument_identifier' => 'tes-kemampuan-kognitif-adaptasi-104',
            'product_name' => 'Tes Kemampuan Kognitif Adaptasi',
            'instrument_version' => '1.0.0-test',
            'question_bank_version' => self::QUESTION_BANK_VERSION,
            'scoring_rule_version' => '1.0.0-test',
            'media_version' => '1.0.0-test',
            'report_version' => '1.0.0-test',
            'norm_version' => null,
            'record_version' => self::RECORD_VERSION,
            'status' => 'frozen',
            'active' => false,
            'scored_question_count' => 104,
            'example_count' => 9,
            'subtest_order' => ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'],
            'duration_seconds' => 2700,
            'memorization_duration_seconds' => 120,
            'answering_duration_seconds' => 240,
            'subtests' => $subtestEntries,
            'files' => [
                'approvals' => 'approvals.json',
                'checksums' => 'checksums.json',
                'media_metadata' => 'media/metadata.json',
            ],
            'media' => [
                'root' => 'media',
                'count' => count($media),
            ],
            'provenance' => [
                'owner' => 'test-fixture:owner',
                'creation_method' => 'programmatically-generated-test-content',
                'usage_scope' => 'automated-testing-only',
            ],
            'approval' => [
                'required' => true,
                'file' => 'approvals.json',
            ],
            'freeze' => [
                'frozen_at' => '2026-08-05T02:00:00Z',
                'frozen_by' => 'test-fixture:freeze-process',
                'freeze_version' => self::QUESTION_BANK_VERSION,
            ],
            'checksum_algorithm' => 'sha256',
            'frozen_at' => '2026-08-05T02:00:00Z',
            'notes' => '[TEST-FIXTURE] Synthetic, non-production, and never activatable.',
        ];

        $approvals = [
            'schema_version' => 1,
            'instrument_identifier' => $manifest['instrument_identifier'],
            'question_bank_version' => self::QUESTION_BANK_VERSION,
            'content_author' => 'test-fixture:content-author',
            'language_reviewer' => 'test-fixture:language-reviewer',
            'logic_reviewer' => 'test-fixture:logic-reviewer',
            'owner_approver' => 'test-fixture:owner-approver',
            'visual_reviewer' => 'test-fixture:visual-reviewer',
            'approved_at' => '2026-08-05T01:00:00Z',
            'visual_approved_at' => '2026-08-05T01:30:00Z',
            'decision' => 'approved',
            'approval_version' => self::QUESTION_BANK_VERSION,
            'notes' => '[TEST-FIXTURE] Automated contract approval, not a product approval.',
        ];

        self::writeJson($directory.'/manifest.json', $manifest);
        self::writeJson($directory.'/approvals.json', $approvals);
        self::writeJson($directory.'/media/metadata.json', [
            'schema_version' => 1,
            'instrument_identifier' => $manifest['instrument_identifier'],
            'question_bank_version' => self::QUESTION_BANK_VERSION,
            'media' => array_values($media),
        ]);
        self::refreshChecksums($directory);

        return $directory;
    }

    public static function refreshChecksums(string $directory): void
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));

            if ($relative !== 'checksums.json') {
                $files[$relative] = hash_file('sha256', $file->getPathname());
            }
        }

        ksort($files, SORT_STRING);
        self::writeJson($directory.'/checksums.json', [
            'algorithm' => 'sha256',
            'generated_at' => '2026-08-05T03:00:00Z',
            'files' => $files,
        ]);
    }

    public static function readJson(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function writeJson(string $path, array $payload): void
    {
        $parent = dirname($path);

        if (! is_dir($parent)) {
            mkdir($parent, 0750, true);
        }

        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n",
        );
    }

    public static function remove(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($directory);
    }

    private static function subtestPayload(array $definition): array
    {
        $code = $definition['code'];
        $type = $definition['default_answer_type'];
        $questions = [self::question($code, $type, 'example', 1, null)];
        $distribution = $definition['question_count'] === 12
            ? [...array_fill(0, 4, 'easy'), ...array_fill(0, 5, 'medium'), ...array_fill(0, 3, 'hard')]
            : [...array_fill(0, 3, 'easy'), ...array_fill(0, 4, 'medium'), ...array_fill(0, 3, 'hard')];

        foreach ($distribution as $index => $difficulty) {
            $questions[] = self::question($code, $type, 'scored', $index + 1, $difficulty);
        }

        return [
            'schema_version' => 1,
            'instrument_identifier' => 'tes-kemampuan-kognitif-adaptasi-104',
            'question_bank_version' => self::QUESTION_BANK_VERSION,
            'subtest_code' => $code,
            'instruction_content' => "[TEST-FIXTURE] Instruksi sintetis {$code} untuk pengujian pipeline.",
            'memorization_content' => $code === 'ME'
                ? '[TEST-FIXTURE] Materi hafalan sintetis untuk pengujian pipeline.'
                : null,
            'questions' => $questions,
        ];
    }

    private static function question(
        string $code,
        string $answerType,
        string $kind,
        int $number,
        ?string $difficulty,
    ): array {
        $prefix = $kind === 'scored' ? 'S' : 'E';
        $logicalId = sprintf('%s-%s-%03d', $code, $prefix, $number);
        $scoring = ['max_score' => $answerType === IstAnswerType::SINGLE_CHOICE_WEIGHTED ? 4 : 1];

        if ($answerType === IstAnswerType::NUMERIC) {
            $scoring['canonical_answer'] = (string) ($number + 10);
        }

        if ($answerType === IstAnswerType::SINGLE_CHOICE_WEIGHTED) {
            $scoring['rationale'] = '[TEST-FIXTURE] Bobot sintetis 4–0 untuk memvalidasi kontrak partial.';
        }

        return [
            'logical_id' => $logicalId,
            'subtest_code' => $code,
            'kind' => $kind,
            'question_number' => $number,
            'display_order' => $number,
            'answer_type' => $answerType,
            'difficulty_target' => $difficulty,
            'prompt' => "[TEST-FIXTURE] Prompt sintetis {$logicalId}; bukan soal produk.",
            'explanation' => $kind === 'example'
                ? '[TEST-FIXTURE] Penjelasan sintetis untuk example.'
                : null,
            'active' => false,
            'source_version' => self::RECORD_VERSION,
            'source' => ['reference' => "test-fixture/{$logicalId}"],
            'provenance' => [
                'owner' => 'test-fixture:owner',
                'origin' => 'programmatically-generated',
            ],
            'review_status' => 'approved',
            'scoring' => $scoring,
            'options' => self::options($answerType, $number),
            'media' => ['prompt_ref' => null],
            'metadata' => [
                'public' => ['display_hint' => 'standard'],
                'internal' => ['test_fixture' => true],
            ],
        ];
    }

    private static function options(string $answerType, int $number): array
    {
        if ($answerType === IstAnswerType::NUMERIC) {
            return [];
        }

        $keys = ['A', 'B', 'C', 'D', 'E'];
        $correctIndex = ($number - 1) % 5;

        return array_map(function (string $key, int $index) use ($answerType, $correctIndex): array {
            $isCorrect = $index === $correctIndex;
            $score = $answerType === IstAnswerType::SINGLE_CHOICE_WEIGHTED
                ? [4, 3, 2, 1, 0][($index - $correctIndex + 5) % 5]
                : ($isCorrect ? 1 : 0);

            return [
                'key' => $key,
                'text' => "Pilihan sintetis {$key}",
                'display_order' => $index + 1,
                'correct' => $isCorrect,
                'score' => $score,
                'media_ref' => $answerType === IstAnswerType::IMAGE_CHOICE
                    ? 'media-option-'.strtolower($key)
                    : null,
            ];
        }, $keys, array_keys($keys));
    }

    private static function createMedia(string $directory): array
    {
        $records = [];

        foreach (['a', 'b', 'c', 'd', 'e'] as $index => $key) {
            $relative = "media/options/option-{$key}.svg";
            $path = $directory.'/'.$relative;
            $offset = 8 + ($index * 4);
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
  <rect x="4" y="4" width="56" height="56" rx="8" fill="#ffffff" stroke="#111827" stroke-width="2"/>
  <circle cx="32" cy="32" r="{$offset}" fill="none" stroke="#2563eb" stroke-width="3"/>
</svg>
SVG;
            file_put_contents($path, $svg."\n");
            $records[] = [
                'logical_id' => "media-option-{$key}",
                'relative_path' => $relative,
                'mime_type' => 'image/svg+xml',
                'byte_size' => filesize($path),
                'width' => 64,
                'height' => 64,
                'sha256' => hash_file('sha256', $path),
                'alt_text' => 'Ilustrasi pilihan '.strtoupper($key),
                'owner' => 'test-fixture:owner',
                'provenance' => 'programmatically-generated-test-media',
                'review_status' => 'approved',
            ];
        }

        return $records;
    }
}
