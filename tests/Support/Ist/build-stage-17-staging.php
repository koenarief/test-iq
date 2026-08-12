<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$draftRoot = $root.'/docs/ist/content-drafts';
$target = $root.'/database/data/ist-final-staging';
$build = $root.'/database/data/.ist-final-staging-build-'.bin2hex(random_bytes(5));
$instrument = 'tes-kemampuan-kognitif-adaptasi-104';
$version = '2026.08.06-stage17';
$recordVersion = 100000017;
$createdAt = '2026-08-06T12:00:00+07:00';
$disclaimer = 'Hasil ini merupakan skor internal berdasarkan sembilan subtes. Nilai ini belum merupakan skor IQ atau interpretasi normatif.';

if (is_dir($target) || is_link($target)) {
    fwrite(STDERR, "Target staging sudah ada; builder menolak menimpa artefak.\n");
    exit(1);
}

$read = static function (string $path): string {
    $contents = @file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException("Tidak dapat membaca {$path}");
    }

    return $contents;
};

$readJson = static function (string $path) use ($read): array {
    return json_decode($read($path), true, 512, JSON_THROW_ON_ERROR);
};

$writeJson = static function (string $path, array $payload): void {
    $directory = dirname($path);

    if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
        throw new RuntimeException("Tidak dapat membuat {$directory}");
    }

    $encoded = json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    )."\n";

    if (file_put_contents($path, $encoded) === false) {
        throw new RuntimeException("Tidak dapat menulis {$path}");
    }
};

$finalLogicalId = static function (string $draftId): string {
    if (preg_match('/\A([a-z]{2})-(example-)?([0-9]{3})\z/', $draftId, $matches) !== 1) {
        throw new RuntimeException("Logical ID draft tidak valid: {$draftId}");
    }

    return strtoupper($matches[1]).'-'.($matches[2] === 'example-' ? 'E' : 'S').'-'.$matches[3];
};

$metadata = static function (string $draftPath, string $draftId) use ($recordVersion): array {
    return [
        'active' => false,
        'source_version' => $recordVersion,
        'source' => [
            'reference' => "internal-draft/{$draftPath}#{$draftId}",
        ],
        'source_reference' => null,
        'provenance' => [
            'owner' => 'internal-assessment-content-team',
            'origin' => 'original_internal',
        ],
        'content_origin' => 'original_internal',
        'copyright_status' => 'internally_authored',
        'normative_compatibility' => 'none',
        'review_status' => 'human_review_passed',
    ];
};

$questionRecord = static function (
    string $draftPath,
    string $draftId,
    string $code,
    string $kind,
    int $displayOrder,
    string $answerType,
    ?string $difficulty,
    string $prompt,
    ?string $explanation,
    array $scoring,
    array $options,
    ?string $promptMedia,
    array $internal,
) use ($finalLogicalId, $metadata): array {
    return [
        'logical_id' => $finalLogicalId($draftId),
        'subtest_code' => $code,
        'kind' => $kind,
        'question_number' => $kind === 'example' ? 1 : $displayOrder,
        'display_order' => $kind === 'example' ? 1 : $displayOrder,
        'answer_type' => $answerType,
        'difficulty_target' => $difficulty,
        'prompt' => $prompt,
        'explanation' => $kind === 'example' ? $explanation : null,
        ...$metadata($draftPath, $draftId),
        'scoring' => $scoring,
        'options' => $options,
        'media' => ['prompt_ref' => $promptMedia],
        'metadata' => [
            'public' => ['display_hint' => $answerType === 'image_choice' ? 'visual' : 'standard'],
            'internal' => ['draft_logical_id' => $draftId, ...$internal],
        ],
    ];
};

$markdownBlocks = static function (string $contents, array $codes): array {
    $pattern = '/^### (('.implode('|', array_map('preg_quote', $codes)).')-(?:example-)?[0-9]{3})\R(.*?)(?=^### |\z)/ms';
    preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

    return array_map(static fn (array $match): array => [
        'id' => $match[1],
        'code' => strtoupper($match[2]),
        'body' => $match[3],
    ], $matches);
};

$contract = static function (string $body, string $id): array {
    if (preg_match('/Contract: `subtest_code=([A-Z]{2})`; `kind=(example|scored)`; `display_order=([0-9]+)`; `difficulty_target=([^`;]+)`; `answer_type=([a-z_]+)`/', $body, $match) !== 1) {
        throw new RuntimeException("Contract tidak ditemukan: {$id}");
    }

    return [
        'code' => $match[1],
        'kind' => $match[2],
        'display_order' => (int) $match[3],
        'difficulty' => $match[4] === 'null' ? null : $match[4],
        'answer_type' => $match[5],
    ];
};

$lineValue = static function (string $body, string $label, string $id, bool $nullable = false): ?string {
    if (preg_match('/^- '.preg_quote($label, '/').': (.+)$/m', $body, $match) !== 1) {
        if ($nullable) {
            return null;
        }

        throw new RuntimeException("{$label} tidak ditemukan: {$id}");
    }

    return trim($match[1]);
};

$parseBinaryMarkdown = static function (string $path, array $codes) use (
    $read,
    $markdownBlocks,
    $contract,
    $lineValue,
    $questionRecord,
): array {
    $records = [];

    foreach ($markdownBlocks($read($path), $codes) as $block) {
        $id = $block['id'];
        $body = $block['body'];
        $definition = $contract($body, $id);
        $prompt = $lineValue($body, 'Prompt', $id);
        $optionsLine = $lineValue($body, 'Options', $id);
        $options = [];

        foreach (preg_split('/;\s*/u', rtrim((string) $optionsLine, '.')) as $index => $optionText) {
            if (preg_match('/\A([A-E])\. (.+?) \(`([01])`(?:, key)?\)\z/u', $optionText, $optionMatch) !== 1) {
                throw new RuntimeException("Opsi binary tidak dapat diparse: {$id}: {$optionText}");
            }

            $score = (int) $optionMatch[3];
            $options[] = [
                'key' => $optionMatch[1],
                'text' => $optionMatch[2],
                'display_order' => $index + 1,
                'correct' => $score === 1,
                'score' => $score,
                'media_ref' => null,
            ];
        }

        $explanation = $definition['kind'] === 'example'
            ? $lineValue($body, 'Explanation peserta', $id)
            : null;
        $rationale = $lineValue($body, 'Rationale internal', $id);
        $records[$definition['code']][] = $questionRecord(
            basename($path),
            $id,
            $definition['code'],
            $definition['kind'],
            $definition['display_order'],
            $definition['answer_type'],
            $definition['difficulty'],
            (string) $prompt,
            $explanation,
            ['max_score' => 1],
            $options,
            null,
            ['rationale' => $rationale],
        );
    }

    return $records;
};

$parseWeightedMarkdown = static function (string $path) use (
    $read,
    $markdownBlocks,
    $contract,
    $lineValue,
    $questionRecord,
): array {
    $records = [];

    foreach ($markdownBlocks($read($path), ['ge']) as $block) {
        $id = $block['id'];
        $body = $block['body'];
        $definition = $contract($body, $id);
        preg_match_all('/^\s+- ([A-E])\. `text=(.*?)`; `score_value=([0-4])`; `is_correct=(true|false)`\.$/mu', $body, $matches, PREG_SET_ORDER);

        if (count($matches) !== 5) {
            throw new RuntimeException("Opsi GE tidak lengkap: {$id}");
        }

        $options = [];
        foreach ($matches as $index => $match) {
            $options[] = [
                'key' => $match[1],
                'text' => $match[2],
                'display_order' => $index + 1,
                'correct' => $match[4] === 'true',
                'score' => (int) $match[3],
                'media_ref' => null,
            ];
        }

        $rationale = $lineValue($body, 'Rationale internal', $id);
        $records['GE'][] = $questionRecord(
            basename($path),
            $id,
            'GE',
            $definition['kind'],
            $definition['display_order'],
            'single_choice_weighted',
            $definition['difficulty'],
            (string) $lineValue($body, 'Prompt', $id),
            $definition['kind'] === 'example' ? $lineValue($body, 'Explanation peserta', $id) : null,
            ['max_score' => 4, 'rationale' => $rationale],
            $options,
            null,
            ['rationale' => $rationale],
        );
    }

    return $records;
};

$parseNumericMarkdown = static function (string $path) use (
    $read,
    $markdownBlocks,
    $contract,
    $lineValue,
    $questionRecord,
): array {
    $records = [];

    foreach ($markdownBlocks($read($path), ['ra', 'zr']) as $block) {
        $id = $block['id'];
        $body = $block['body'];
        $definition = $contract($body, $id);

        if (preg_match('/^- `numeric_answer_key`: `"([0-9]+)"`\.$/m', $body, $keyMatch) !== 1) {
            throw new RuntimeException("Canonical numeric key tidak ditemukan: {$id}");
        }

        $rationale = $lineValue($body, 'Rationale internal', $id);
        $records[$definition['code']][] = $questionRecord(
            basename($path),
            $id,
            $definition['code'],
            $definition['kind'],
            $definition['display_order'],
            'numeric',
            $definition['difficulty'],
            (string) $lineValue($body, 'Prompt', $id),
            $definition['kind'] === 'example' ? $lineValue($body, 'Explanation peserta', $id) : null,
            ['max_score' => 1, 'canonical_answer' => $keyMatch[1]],
            [],
            null,
            ['rationale' => $rationale],
        );
    }

    return $records;
};

$mergeByCode = static function (array ...$groups): array {
    $merged = [];
    foreach ($groups as $group) {
        foreach ($group as $code => $records) {
            $merged[$code] = [...($merged[$code] ?? []), ...$records];
        }
    }

    return $merged;
};

$questions = $mergeByCode(
    $parseBinaryMarkdown($draftRoot.'/stage-12-se-wa-an.md', ['se', 'wa', 'an']),
    $parseWeightedMarkdown($draftRoot.'/stage-13-ge-weighted.md'),
    $parseNumericMarkdown($draftRoot.'/stage-14-ra-zr-numeric.md'),
);

$geometry = $readJson($draftRoot.'/stage-15-assets/geometry-spec-draft.json');
foreach ($geometry['records'] as $record) {
    $code = $record['subtest_code'];
    $options = array_map(static fn (array $option, int $index): array => [
        'key' => $option['code'],
        'text' => null,
        'display_order' => $index + 1,
        'correct' => $option['is_correct'],
        'score' => $option['score_value'],
        'media_ref' => $option['media_id'],
    ], $record['options'], array_keys($record['options']));
    $questions[$code][] = $questionRecord(
        'stage-15-assets/geometry-spec-draft.json',
        $record['logical_id'],
        $code,
        $record['kind'],
        $record['display_order'],
        'image_choice',
        $record['difficulty_target'],
        $record['prompt_text'],
        $record['explanation'],
        ['max_score' => 1],
        $options,
        $record['prompt_media_id'],
        ['rationale' => $record['rationale_internal']],
    );
}

$me = $readJson($draftRoot.'/stage-16-me-data-draft.json');
$meRecords = [$me['example'], ...$me['questions']];
foreach ($meRecords as $record) {
    $options = array_map(static fn (array $option, int $index): array => [
        'key' => $option['code'],
        'text' => $option['text'],
        'display_order' => $index + 1,
        'correct' => $option['is_correct'],
        'score' => $option['score_value'],
        'media_ref' => null,
    ], $record['options'], array_keys($record['options']));
    $questions['ME'][] = $questionRecord(
        'stage-16-me-data-draft.json',
        $record['logical_id'],
        'ME',
        $record['kind'],
        $record['display_order'],
        'single_choice',
        $record['difficulty_target'],
        $record['prompt'],
        $record['explanation'],
        ['max_score' => 1],
        $options,
        null,
        array_filter([
            'rationale' => $record['rationale_internal'],
            'recall_direction' => $record['recall_direction'] ?? null,
            'target_pair_id' => $record['target_pair_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null),
    );
}

$catalog = [
    'SE' => [12, 240, 0, 240, 'single_choice', 12, 'Pilih satu kata atau frasa yang paling tepat untuk melengkapi kalimat. Gunakan makna seluruh kalimat, bukan hanya kecocokan tata bahasa.'],
    'WA' => [12, 240, 0, 240, 'single_choice', 12, 'Empat pilihan mempunyai kategori atau fungsi bersama. Pilih satu pilihan yang tidak termasuk kelompok tersebut.'],
    'AN' => [12, 240, 0, 240, 'single_choice', 12, 'Tentukan hubungan pada pasangan pertama, lalu pilih kata yang membentuk hubungan paling setara pada pasangan kedua.'],
    'GE' => [10, 300, 0, 300, 'single_choice_weighted', 40, 'Setiap soal menampilkan dua konsep. Pilih opsi yang paling tepat menjelaskan persamaan utama keduanya.'],
    'RA' => [12, 360, 0, 360, 'numeric', 12, 'Bacalah situasi hitung dengan cermat. Masukkan satu bilangan bulat tanpa satuan atau pemisah ribuan.'],
    'ZR' => [12, 360, 0, 360, 'numeric', 12, 'Temukan aturan paling sederhana yang konsisten pada deret, lalu masukkan satu bilangan berikutnya.'],
    'FA' => [10, 240, 0, 240, 'image_choice', 10, 'Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.'],
    'WU' => [12, 360, 0, 360, 'image_choice', 12, 'Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.'],
    'ME' => [12, 360, 120, 240, 'single_choice', 23, $me['instruction']],
];

$subtestEntries = [];
$subtestHashes = [];
$answerTypeCounts = ['single_choice' => 0, 'single_choice_weighted' => 0, 'numeric' => 0, 'image_choice' => 0];
$optionCount = 0;

foreach ($catalog as $code => [$count, $duration, $memory, $answering, $type, $maxScore, $instruction]) {
    $records = $questions[$code] ?? [];
    usort($records, static fn (array $left, array $right): int => [
        $left['kind'] === 'example' ? 0 : 1,
        $left['display_order'],
    ] <=> [
        $right['kind'] === 'example' ? 0 : 1,
        $right['display_order'],
    ]);

    $safeMePairs = array_map(static fn (array $pair): array => [
        'cue' => $pair['cue'],
        'associate' => $pair['associate'],
        'display_order' => $pair['display_order'],
    ], $me['main_pairs']);
    $payload = [
        'schema_version' => 1,
        'instrument_identifier' => $instrument,
        'question_bank_version' => $version,
        'subtest_code' => $code,
        'review_status' => 'human_review_passed',
        'active' => false,
        'content_origin' => 'original_internal',
        'source_reference' => null,
        'copyright_status' => 'internally_authored',
        'normative_compatibility' => 'none',
        'instruction_content' => $instruction,
        'memorization_content' => $code === 'ME'
            ? json_encode(['pairs' => $safeMePairs], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : null,
        'memorization' => $code === 'ME' ? [
            'main_pairs' => $me['main_pairs'],
            'example_pairs' => $me['example']['pairs'],
            'tested_count' => 12,
            'filler_count' => 3,
            'forward_count' => 6,
            'reverse_count' => 6,
        ] : null,
        'questions' => $records,
    ];

    $file = strtolower($code).'.json';
    $writeJson($build.'/'.$file, $payload);
    $subtestHashes[$file] = hash_file('sha256', $build.'/'.$file);
    $subtestEntries[] = [
        'code' => $code,
        'file' => $file,
        'answer_type' => $type,
        'scored_question_count' => $count,
        'example_count' => 1,
        'duration_seconds' => $duration,
        'memorization_duration_seconds' => $memory,
        'answering_duration_seconds' => $answering,
        'max_score' => $maxScore,
    ];

    foreach ($records as $record) {
        if ($record['kind'] === 'scored') {
            $answerTypeCounts[$record['answer_type']]++;
        }
        $optionCount += count($record['options']);
    }
}

$sourceMedia = $readJson($draftRoot.'/stage-15-assets/media-manifest-draft.json');
$hashGroups = [];
foreach ($sourceMedia['media'] as $record) {
    $hashGroups[$record['sha256']][] = $record['logical_id'];
}

$mediaRecords = [];
$mediaAggregateParts = [];
foreach ($sourceMedia['media'] as $record) {
    $source = $draftRoot.'/stage-15-assets/'.$record['relative_path'];
    $relative = 'media/'.$record['relative_path'];
    $destination = $build.'/'.$relative;

    if (! is_dir(dirname($destination)) && ! mkdir(dirname($destination), 0750, true) && ! is_dir(dirname($destination))) {
        throw new RuntimeException('Tidak dapat membuat directory media staging');
    }

    if (! copy($source, $destination)) {
        throw new RuntimeException("Gagal menyalin media {$source}");
    }

    if (! hash_equals($record['sha256'], hash_file('sha256', $destination))) {
        throw new RuntimeException("Checksum media berubah saat staging: {$record['logical_id']}");
    }

    $final = [
        'logical_id' => $record['logical_id'],
        'relative_path' => $relative,
        'role' => $record['media_role'],
        'subtest_code' => $record['subtest_code'],
        'question_logical_id' => $finalLogicalId($record['question_logical_id']),
        'option_code' => $record['option_code'],
        'mime_type' => $record['mime_type'],
        'byte_size' => $record['byte_size'],
        'width' => $record['width'],
        'height' => $record['height'],
        'view_box' => $record['view_box'],
        'sha256' => $record['sha256'],
        'alt_text' => $record['alt_text'],
        'owner' => 'internal-assessment-content-team',
        'provenance' => 'stage-15-human-reviewed-original-svg',
        'content_origin' => 'original_internal',
        'copyright_status' => 'internally_authored',
        'review_status' => 'human_review_passed',
        'active' => false,
    ];

    if (count($hashGroups[$record['sha256']]) > 1) {
        $final['duplicate_hash_reason'] = 'Reviewed geometry is intentionally reused across distinct neutral views.';
    }

    $mediaRecords[] = $final;
    $mediaAggregateParts[] = $record['logical_id'].':'.$record['sha256'];
}

$writeJson($build.'/media/metadata.json', [
    'schema_version' => 1,
    'instrument_identifier' => $instrument,
    'question_bank_version' => $version,
    'review_status' => 'human_review_passed',
    'active' => false,
    'media' => $mediaRecords,
]);

ksort($subtestHashes, SORT_STRING);
sort($mediaAggregateParts, SORT_STRING);
$contentFingerprint = hash('sha256', implode("\n", array_map(
    static fn (string $path, string $hash): string => "{$path}:{$hash}",
    array_keys($subtestHashes),
    array_values($subtestHashes),
)));
$mediaAggregate = hash('sha256', implode("\n", $mediaAggregateParts));

$approvals = [
    'schema_version' => 1,
    'instrument_identifier' => $instrument,
    'question_bank_version' => $version,
    'review_status' => 'human_review_passed',
    'human_content_review_complete' => true,
    'human_visual_review_complete' => true,
    'decision' => 'not_approved',
    'approved' => false,
    'approved_at' => null,
    'approval_version' => null,
    'frozen' => false,
    'imported' => false,
    'notes' => 'Stage 17 consolidation only; approval and freeze require separate explicit checkpoints.',
];
$writeJson($build.'/approvals.json', $approvals);

$manifest = [
    'schema_version' => 1,
    'dataset_type' => 'final_staging',
    'dataset_version' => $version,
    'creation_timestamp' => $createdAt,
    'instrument_identifier' => $instrument,
    'product_code' => $instrument,
    'product_name' => 'Tes Kemampuan Kognitif Adaptasi',
    'display_name' => 'Tes Kemampuan Kognitif Adaptasi',
    'subtitle' => 'Mengukur performa pada sembilan area kemampuan kognitif melalui asesmen singkat sekitar 45 menit.',
    'result_disclaimer' => $disclaimer,
    'instrument_version' => '1.0.0-staging',
    'question_bank_version' => $version,
    'scoring_rule_version' => '1.0.0',
    'media_version' => '1.0.0-staging',
    'report_version' => '1.0.0',
    'norm_version' => null,
    'record_version' => $recordVersion,
    'status' => 'human_review_passed',
    'review_status' => 'human_review_passed',
    'consolidated' => true,
    'approved' => false,
    'frozen' => false,
    'active' => false,
    'imported' => false,
    'normative' => false,
    'iq_output' => false,
    'scored_question_count' => 104,
    'example_count' => 9,
    'subtest_count' => 9,
    'estimated_core_minutes' => 45,
    'total_option_count' => $optionCount,
    'answer_type_counts_scored' => $answerTypeCounts,
    'subtest_order' => array_keys($catalog),
    'duration_seconds' => 2700,
    'memorization_duration_seconds' => 120,
    'answering_duration_seconds' => 240,
    'subtest_percentage_formula' => 'awarded_score / max_subtest_score * 100',
    'total_internal_score_method' => 'mean_of_four_domain_scores',
    'subtests' => $subtestEntries,
    'files' => [
        'approvals' => 'approvals.json',
        'checksums' => 'checksums.json',
        'media_metadata' => 'media/metadata.json',
    ],
    'media' => ['root' => 'media', 'count' => count($mediaRecords)],
    'source_draft_stages' => [
        'stage-12-se-wa-an.md',
        'stage-13-ge-weighted.md',
        'stage-14-ra-zr-numeric.md',
        'stage-15-assets/geometry-spec-draft.json',
        'stage-15-assets/media-manifest-draft.json',
        'stage-16-me-data-draft.json',
    ],
    'provenance' => [
        'owner' => 'internal-assessment-content-team',
        'creation_method' => 'deterministic-consolidation-from-human-reviewed-internal-drafts',
        'usage_scope' => 'inactive-final-staging-validation-only',
    ],
    'approval' => ['required' => true, 'completed' => false, 'file' => 'approvals.json'],
    'freeze' => ['frozen' => false, 'frozen_at' => null, 'frozen_by' => null, 'freeze_version' => null],
    'checksum_algorithm' => 'sha256',
    'content_fingerprint' => $contentFingerprint,
    'media_checksum_aggregate' => $mediaAggregate,
    'disclaimer_fingerprint' => hash('sha256', $disclaimer),
    'notes' => 'Human-reviewed inactive staging dataset. Not approved, frozen, imported, or activatable.',
];
$writeJson($build.'/manifest.json', $manifest);

$checksumFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($build, RecursiveDirectoryIterator::SKIP_DOTS),
);
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($build) + 1));
        $checksumFiles[$relative] = hash_file('sha256', $file->getPathname());
    }
}
ksort($checksumFiles, SORT_STRING);
$writeJson($build.'/checksums.json', [
    'algorithm' => 'sha256',
    'generated_at' => '2026-08-06T12:05:00+07:00',
    'files' => $checksumFiles,
]);

if (! rename($build, $target)) {
    throw new RuntimeException('Tidak dapat memindahkan hasil build ke target staging');
}

// Replace the legacy Stage 16 paired-associate draft with the canonical final
// initial-letter-to-category ME data and synchronize staging fingerprints.
require $root.'/scripts/generate-me-final.php';

echo "Stage 17 staging dataset dibangun.\n";
echo "Subtests=9; examples=9; scored=104; options={$optionCount}; media=".count($mediaRecords)."\n";
echo "Content fingerprint={$contentFingerprint}\n";
echo "Media aggregate={$mediaAggregate}\n";
