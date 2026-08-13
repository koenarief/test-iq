<?php

namespace App\Services\Ist\Import\Final;

use App\Data\Ist\IstFinalDatasetValidationResult;
use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Models\Ist\IstQuestion;
use App\Support\Ist\IstAnswerType;
use App\Support\Ist\IstSubtestCatalog;
use Carbon\CarbonImmutable;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

final class IstFinalQuestionDatasetValidator
{
    private const INSTRUMENT_IDENTIFIER = 'tes-kemampuan-kognitif-adaptasi-104';

    private const PRODUCT_NAME = 'Tes Kemampuan Kognitif Adaptasi';

    private const SUBTEST_ORDER = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];

    private const VERSION_PATTERN = '/\A[a-z0-9][a-z0-9._-]{0,63}\z/';

    private const SHA256_PATTERN = '/\A[a-f0-9]{64}\z/';

    private const ALLOWED_DIFFICULTIES = ['easy', 'medium', 'hard'];

    private array $logicalIds = [];

    public function __construct(
        private readonly IstFinalMediaValidator $mediaValidator,
    ) {}

    public function validate(string $datasetDirectory): IstFinalDatasetValidationResult
    {
        return $this->validatePackage($datasetDirectory, true);
    }

    public function validateStaging(string $datasetDirectory): IstFinalDatasetValidationResult
    {
        return $this->validatePackage($datasetDirectory, false);
    }

    private function validatePackage(
        string $datasetDirectory,
        bool $finalized,
    ): IstFinalDatasetValidationResult {
        $this->logicalIds = [];
        $directory = $this->canonicalDirectory($datasetDirectory);
        $manifest = $this->readJson($directory.'/manifest.json', 'manifest.json');
        $this->validateManifest($manifest, $finalized);

        $approvalsPath = $this->declaredPath($manifest['files']['approvals'] ?? null, 'approvals');
        $checksumsPath = $this->declaredPath($manifest['files']['checksums'] ?? null, 'checksums');
        $mediaMetadataPath = $this->declaredPath($manifest['files']['media_metadata'] ?? null, 'media metadata');
        $approvals = $this->readJson($directory.'/'.$approvalsPath, $approvalsPath);
        $checksums = $this->readJson($directory.'/'.$checksumsPath, $checksumsPath);
        $mediaPayload = $this->readJson($directory.'/'.$mediaMetadataPath, $mediaMetadataPath);
        $media = $this->mediaValidator->validate(
            $directory,
            $manifest,
            $mediaPayload,
            $finalized ? 'approved' : 'human_review_passed',
        );

        $this->validateApprovalsAndFreeze($manifest, $approvals, $checksums, $finalized);

        $subtestFiles = [];

        foreach ($manifest['subtests'] as $entry) {
            $subtestFiles[$entry['code']] = $this->declaredPath(
                $entry['file'] ?? null,
                "subtest {$entry['code']}",
            );
        }

        $expectedChecksummedFiles = [
            'manifest.json',
            $approvalsPath,
            $mediaMetadataPath,
            ...array_values($subtestFiles),
            ...array_column(array_values($media), 'relative_path'),
        ];

        $this->validateChecksums(
            $directory,
            $checksumsPath,
            $checksums,
            $expectedChecksummedFiles,
        );
        $this->assertNoForeignFiles(
            $directory,
            [...$expectedChecksummedFiles, $checksumsPath],
        );

        $catalog = array_column(IstSubtestCatalog::all(), null, 'code');
        $subtests = [];
        $scoredTotal = 0;
        $exampleTotal = 0;
        $optionTotal = 0;

        foreach (self::SUBTEST_ORDER as $code) {
            $payload = $this->readJson($directory.'/'.$subtestFiles[$code], $subtestFiles[$code]);
            [$scored, $examples, $options] = $this->validateSubtest(
                $payload,
                $manifest,
                $catalog[$code],
                $media,
                $finalized,
            );
            $subtests[$code] = $payload;
            $scoredTotal += $scored;
            $exampleTotal += $examples;
            $optionTotal += $options;
        }

        if ($scoredTotal !== 104 || $exampleTotal !== 9) {
            $this->fail("total aktual harus 104 scored dan 9 example, ditemukan {$scoredTotal}/{$exampleTotal}");
        }

        if (! $finalized) {
            $this->validateStagingAggregate($manifest, $subtests, $media, $optionTotal, $checksums);
        }

        $this->assertNoForbiddenMarkers([
            $manifest,
            $approvals,
            $checksums,
            $mediaPayload,
            $subtests,
        ]);

        return new IstFinalDatasetValidationResult(
            directory: $directory,
            manifest: $manifest,
            approvals: $approvals,
            checksums: $checksums,
            subtests: $subtests,
            media: $media,
            scoredQuestionCount: $scoredTotal,
            exampleQuestionCount: $exampleTotal,
            optionCount: $optionTotal,
        );
    }

    private function validateManifest(array $manifest, bool $finalized): void
    {
        $expectedIdentity = [
            'schema_version' => 1,
            'dataset_type' => $finalized ? 'final' : 'final_staging',
            'instrument_identifier' => (string) config('ist.final_instrument_identifier', self::INSTRUMENT_IDENTIFIER),
            'product_name' => (string) config('ist.final_product_name', self::PRODUCT_NAME),
            'status' => $finalized ? 'frozen' : 'human_review_passed',
            'active' => false,
            'scored_question_count' => 104,
            'example_count' => 9,
            'subtest_order' => self::SUBTEST_ORDER,
            'duration_seconds' => 2700,
            'memorization_duration_seconds' => 120,
            'answering_duration_seconds' => 240,
            'checksum_algorithm' => 'sha256',
        ];

        foreach ($expectedIdentity as $field => $expected) {
            if (($manifest[$field] ?? null) !== $expected) {
                $this->fail("manifest.{$field} tidak valid");
            }
        }

        if (! $finalized) {
            $stagingIdentity = [
                'product_code' => self::INSTRUMENT_IDENTIFIER,
                'display_name' => self::PRODUCT_NAME,
                'subtitle' => 'Mengukur performa pada sembilan area kemampuan kognitif melalui asesmen singkat sekitar 45 menit.',
                'result_disclaimer' => 'Hasil ini merupakan skor internal berdasarkan sembilan subtes. Nilai ini belum merupakan skor IQ atau interpretasi normatif.',
                'subtest_count' => 9,
                'estimated_core_minutes' => 45,
                'review_status' => 'human_review_passed',
                'consolidated' => true,
                'approved' => false,
                'frozen' => false,
                'imported' => false,
                'normative' => false,
                'iq_output' => false,
                'total_option_count' => 435,
            ];

            foreach ($stagingIdentity as $field => $expected) {
                if (($manifest[$field] ?? null) !== $expected) {
                    $this->fail("manifest staging.{$field} tidak valid");
                }
            }

            foreach (['dataset_version', 'creation_timestamp', 'content_fingerprint', 'media_checksum_aggregate', 'disclaimer_fingerprint'] as $field) {
                if (! is_string($manifest[$field] ?? null) || trim($manifest[$field]) === '') {
                    $this->fail("manifest staging.{$field} wajib diisi");
                }
            }
        }

        foreach ([
            'instrument_version',
            'question_bank_version',
            'scoring_rule_version',
            'media_version',
            'report_version',
        ] as $field) {
            if (! is_string($manifest[$field] ?? null)
                || preg_match(self::VERSION_PATTERN, $manifest[$field]) !== 1) {
                $this->fail("manifest.{$field} tidak valid");
            }
        }

        if (array_key_exists('norm_version', $manifest) && $manifest['norm_version'] !== null) {
            $this->fail('norm_version harus null');
        }

        $recordVersion = $manifest['record_version'] ?? null;
        $minimum = (int) config('ist.final_version_min', 100000000);
        $maximum = (int) config('ist.final_version_max', 899999999);

        if (! is_int($recordVersion)
            || $recordVersion < $minimum
            || $recordVersion > $maximum
            || $recordVersion === 900000001) {
            $this->fail('record_version berada di luar range final atau memakai versi development');
        }

        if (! is_array($manifest['files'] ?? null)
            || ! is_array($manifest['media'] ?? null)
            || ! is_array($manifest['provenance'] ?? null)
            || ! is_array($manifest['approval'] ?? null)
            || ! is_array($manifest['freeze'] ?? null)
            || ! is_string($manifest['notes'] ?? null)) {
            $this->fail('kontrak metadata manifest tidak lengkap');
        }

        if (($manifest['approval']['required'] ?? null) !== true
            || ($manifest['approval']['file'] ?? null) !== ($manifest['files']['approvals'] ?? null)
            || (! $finalized && ($manifest['approval']['completed'] ?? null) !== false)) {
            $this->fail('deklarasi approval manifest tidak konsisten');
        }

        if (($manifest['media']['root'] ?? null) !== 'media'
            || ! is_int($manifest['media']['count'] ?? null)
            || $manifest['media']['count'] < 1) {
            $this->fail('deklarasi media manifest tidak valid');
        }

        foreach (['owner', 'creation_method', 'usage_scope'] as $field) {
            if (! is_string($manifest['provenance'][$field] ?? null)
                || trim($manifest['provenance'][$field]) === '') {
                $this->fail("manifest.provenance.{$field} wajib diisi");
            }
        }

        $subtests = $manifest['subtests'] ?? null;

        if (! is_array($subtests) || count($subtests) !== 9
            || array_column($subtests, 'code') !== self::SUBTEST_ORDER) {
            $this->fail('urutan subtest manifest harus tepat SE sampai ME');
        }

        $catalog = array_column(IstSubtestCatalog::all(), null, 'code');
        $seenFiles = [];

        foreach ($subtests as $entry) {
            $code = $entry['code'];
            $definition = $catalog[$code];
            $expected = [
                'answer_type' => $definition['default_answer_type'],
                'scored_question_count' => $definition['question_count'],
                'example_count' => 1,
                'duration_seconds' => $definition['duration_seconds'],
                'memorization_duration_seconds' => $definition['memorization_seconds'],
                'answering_duration_seconds' => $definition['answering_seconds'],
            ];

            if ($finalized) {
                $expected = [
                    ...$expected,
                    'name' => $definition['name'],
                    'sequence' => $definition['sequence'],
                    'question_count' => $definition['question_count'],
                    'default_answer_type' => $definition['default_answer_type'],
                ];
            }

            foreach ($expected as $field => $value) {
                if (($entry[$field] ?? null) !== $value) {
                    $this->fail("subtest {$code} memiliki {$field} yang tidak sesuai katalog");
                }
            }

            $file = $this->declaredPath($entry['file'] ?? null, "subtest {$code}");

            if (isset($seenFiles[$file])) {
                $this->fail("file subtest duplikat: {$file}");
            }

            $seenFiles[$file] = true;
        }
    }

    private function validateApprovalsAndFreeze(
        array $manifest,
        array $approvals,
        array $checksums,
        bool $finalized,
    ): void {
        if (! $finalized) {
            $expected = [
                'schema_version' => $manifest['schema_version'],
                'instrument_identifier' => $manifest['instrument_identifier'],
                'question_bank_version' => $manifest['question_bank_version'],
                'review_status' => 'human_review_passed',
                'human_content_review_complete' => true,
                'human_visual_review_complete' => true,
                'decision' => 'not_approved',
                'approved' => false,
                'approved_at' => null,
                'approval_version' => null,
                'frozen' => false,
                'imported' => false,
            ];

            foreach ($expected as $field => $value) {
                if (! array_key_exists($field, $approvals) || $approvals[$field] !== $value) {
                    $this->fail("approval staging.{$field} tidak valid");
                }
            }

            if (($manifest['freeze']['frozen'] ?? null) !== false
                || ! array_key_exists('frozen_at', $manifest['freeze'])
                || $manifest['freeze']['frozen_at'] !== null
                || ! array_key_exists('frozen_by', $manifest['freeze'])
                || $manifest['freeze']['frozen_by'] !== null
                || ! array_key_exists('freeze_version', $manifest['freeze'])
                || $manifest['freeze']['freeze_version'] !== null) {
                $this->fail('freeze metadata staging harus eksplisit belum dibekukan');
            }

            if (($checksums['algorithm'] ?? null) !== 'sha256'
                || ! is_string($checksums['generated_at'] ?? null)
                || ! is_array($checksums['files'] ?? null)) {
                $this->fail('metadata checksums staging tidak valid');
            }

            try {
                CarbonImmutable::parse($checksums['generated_at']);
            } catch (Throwable) {
                $this->fail('timestamp checksums staging tidak valid');
            }

            return;
        }

        if (($approvals['schema_version'] ?? null) !== $manifest['schema_version']
            || ($approvals['instrument_identifier'] ?? null) !== $manifest['instrument_identifier']
            || ($approvals['question_bank_version'] ?? null) !== $manifest['question_bank_version']
            || ($approvals['decision'] ?? null) !== 'approved'
            || ($approvals['approval_version'] ?? null) !== $manifest['question_bank_version']) {
            $this->fail('identitas atau keputusan approval tidak valid');
        }

        foreach ([
            'content_author',
            'language_reviewer',
            'logic_reviewer',
            'owner_approver',
            'visual_reviewer',
            'notes',
        ] as $field) {
            $value = $approvals[$field] ?? null;

            if (! is_string($value) || trim($value) === '' || $this->isPlaceholder($value)) {
                $this->fail("approval {$field} kosong atau placeholder");
            }
        }

        foreach (['approved_at', 'visual_approved_at'] as $field) {
            if (! is_string($approvals[$field] ?? null)) {
                $this->fail("approval {$field} wajib diisi");
            }
        }

        foreach (['frozen_at', 'frozen_by', 'freeze_version'] as $field) {
            $value = $manifest['freeze'][$field] ?? null;

            if (! is_string($value) || trim($value) === '' || $this->isPlaceholder($value)) {
                $this->fail("freeze metadata {$field} kosong atau placeholder");
            }
        }

        if (($manifest['frozen_at'] ?? null) !== $manifest['freeze']['frozen_at']) {
            $this->fail('frozen_at manifest tidak konsisten');
        }

        if (($checksums['algorithm'] ?? null) !== 'sha256'
            || ! is_string($checksums['generated_at'] ?? null)
            || ! is_array($checksums['files'] ?? null)) {
            $this->fail('metadata checksums tidak valid');
        }

        try {
            $approvedAt = CarbonImmutable::parse($approvals['approved_at']);
            $visualApprovedAt = CarbonImmutable::parse($approvals['visual_approved_at']);
            $frozenAt = CarbonImmutable::parse($manifest['freeze']['frozen_at']);
            $generatedAt = CarbonImmutable::parse($checksums['generated_at']);
        } catch (Throwable) {
            $this->fail('timestamp approval/freeze/checksum tidak valid');
        }

        if ($approvedAt->greaterThan($frozenAt)
            || $visualApprovedAt->greaterThan($frozenAt)
            || $frozenAt->greaterThan($generatedAt)) {
            $this->fail('urutan waktu approval, freeze, dan checksum tidak konsisten');
        }
    }

    private function validateChecksums(
        string $directory,
        string $checksumsPath,
        array $checksums,
        array $expectedFiles,
    ): void {
        $expectedFiles = array_values(array_unique($expectedFiles));
        sort($expectedFiles, SORT_STRING);
        $declared = $checksums['files'];

        if (array_keys($declared) !== $expectedFiles || array_key_exists($checksumsPath, $declared)) {
            $this->fail('daftar file checksum tidak lengkap, tidak urut, atau circular');
        }

        foreach ($declared as $relativePath => $expectedHash) {
            $path = $this->declaredPath($relativePath, 'checksum');
            $absolute = $directory.'/'.$path;

            if (! is_string($expectedHash) || preg_match(self::SHA256_PATTERN, $expectedHash) !== 1) {
                $this->fail("SHA-256 tidak valid: {$path}");
            }

            if (is_link($absolute) || ! is_file($absolute)) {
                $this->fail("file checksum hilang atau symlink: {$path}");
            }

            if (! hash_equals($expectedHash, hash_file('sha256', $absolute))) {
                $this->fail("checksum file tidak cocok: {$path}");
            }
        }
    }

    private function validateSubtest(
        array $payload,
        array $manifest,
        array $catalog,
        array $media,
        bool $finalized,
    ): array {
        $code = $catalog['code'];

        if (($payload['schema_version'] ?? null) !== $manifest['schema_version']
            || ($payload['instrument_identifier'] ?? null) !== $manifest['instrument_identifier']
            || ($payload['question_bank_version'] ?? null) !== $manifest['question_bank_version']
            || ($payload['subtest_code'] ?? null) !== $code) {
            $this->fail("identitas subtest {$code} tidak valid");
        }

        if (! is_string($payload['instruction_content'] ?? null)
            || trim($payload['instruction_content']) === '') {
            $this->fail("instruction subtest {$code} kosong");
        }

        if (! $finalized) {
            $expectedMetadata = [
                'review_status' => 'human_review_passed',
                'active' => false,
                'content_origin' => 'original_internal',
                'source_reference' => null,
                'copyright_status' => 'internally_authored',
                'normative_compatibility' => 'none',
            ];

            foreach ($expectedMetadata as $field => $expected) {
                if (! array_key_exists($field, $payload) || $payload[$field] !== $expected) {
                    $this->fail("metadata staging subtest {$code}.{$field} tidak valid");
                }
            }
        }

        $memorization = $payload['memorization_content'] ?? null;

        if ($code === 'ME') {
            if (! is_string($memorization) || trim($memorization) === '') {
                $this->fail('memorization content ME wajib tersedia');
            }
        } elseif ($memorization !== null) {
            $this->fail("memorization content hanya boleh ada pada ME, ditemukan pada {$code}");
        }

        $questions = $payload['questions'] ?? null;

        if (! is_array($questions)) {
            $this->fail("questions subtest {$code} harus array");
        }

        $counts = [IstQuestion::KIND_SCORED => 0, IstQuestion::KIND_EXAMPLE => 0];
        $numbers = [IstQuestion::KIND_SCORED => [], IstQuestion::KIND_EXAMPLE => []];
        $orders = [IstQuestion::KIND_SCORED => [], IstQuestion::KIND_EXAMPLE => []];
        $difficulties = array_fill_keys(self::ALLOWED_DIFFICULTIES, 0);
        $optionCount = 0;

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                $this->fail("question {$code}/{$index} harus object");
            }

            $label = "{$code}/".($question['logical_id'] ?? "#{$index}");
            $kind = $question['kind'] ?? null;

            if (! array_key_exists($kind, $counts)) {
                $this->fail("kind question {$label} tidak valid");
            }

            $this->validateCommonQuestion($question, $label, $code, $catalog, $manifest, $finalized);
            $counts[$kind]++;
            $number = $question['question_number'];
            $order = $question['display_order'];

            if (isset($numbers[$kind][$number]) || isset($orders[$kind][$order])) {
                $this->fail("question number atau display order duplikat pada {$label}");
            }

            $numbers[$kind][$number] = true;
            $orders[$kind][$order] = true;

            if ($kind === IstQuestion::KIND_SCORED) {
                $difficulty = $question['difficulty_target'] ?? null;

                if (! in_array($difficulty, self::ALLOWED_DIFFICULTIES, true)) {
                    $this->fail("difficulty target scored question {$label} tidak valid");
                }

                $difficulties[$difficulty]++;
            } elseif (($question['difficulty_target'] ?? null) !== null) {
                $this->fail("example {$label} tidak boleh masuk distribusi difficulty");
            }

            $promptMedia = $question['media']['prompt_ref'] ?? null;

            if ($promptMedia !== null && ! isset($media[$promptMedia])) {
                $this->fail("prompt media question {$label} tidak tersedia");
            }

            $optionCount += $this->validateScoringAndOptions($question, $label, $media, $finalized);
        }

        $expectedCount = $catalog['question_count'];

        if ($counts[IstQuestion::KIND_SCORED] !== $expectedCount
            || $counts[IstQuestion::KIND_EXAMPLE] !== 1) {
            $this->fail("jumlah scored/example {$code} tidak tepat");
        }

        foreach ([IstQuestion::KIND_SCORED => $expectedCount, IstQuestion::KIND_EXAMPLE => 1] as $kind => $maximum) {
            $actualNumbers = array_keys($numbers[$kind]);
            $actualOrders = array_keys($orders[$kind]);
            sort($actualNumbers, SORT_NUMERIC);
            sort($actualOrders, SORT_NUMERIC);

            if ($actualNumbers !== range(1, $maximum) || $actualOrders !== range(1, $maximum)) {
                $this->fail("penomoran lokal {$kind} {$code} harus berurutan mulai 1");
            }
        }

        $expectedDifficulty = $expectedCount === 12
            ? ['easy' => 4, 'medium' => 5, 'hard' => 3]
            : ['easy' => 3, 'medium' => 4, 'hard' => 3];

        if ($difficulties !== $expectedDifficulty) {
            $this->fail("distribusi difficulty {$code} tidak tepat");
        }

        return [$counts[IstQuestion::KIND_SCORED], $counts[IstQuestion::KIND_EXAMPLE], $optionCount];
    }

    private function validateCommonQuestion(
        array $question,
        string $label,
        string $code,
        array $catalog,
        array $manifest,
        bool $finalized,
    ): void {
        $logicalId = $question['logical_id'] ?? null;

        if (! is_string($logicalId)
            || preg_match('/\A[A-Z]{2}-(?:S|E)-[0-9]{3}\z/', $logicalId) !== 1
            || isset($this->logicalIds[$logicalId])) {
            $this->fail("logical ID question tidak valid atau duplikat: {$label}");
        }

        $this->logicalIds[$logicalId] = true;

        if (($question['subtest_code'] ?? null) !== $code
            || ($question['answer_type'] ?? null) !== $catalog['default_answer_type']
            || ($question['active'] ?? null) !== false
            || ($question['review_status'] ?? null) !== ($finalized ? 'approved' : 'human_review_passed')
            || ($question['source_version'] ?? null) !== $manifest['record_version']) {
            $this->fail("kontrak status/type/version question {$label} tidak valid");
        }

        if (! $finalized) {
            $expectedMetadata = [
                'content_origin' => 'original_internal',
                'source_reference' => null,
                'copyright_status' => 'internally_authored',
                'normative_compatibility' => 'none',
            ];

            foreach ($expectedMetadata as $field => $expected) {
                if (! array_key_exists($field, $question) || $question[$field] !== $expected) {
                    $this->fail("metadata staging question {$label}.{$field} tidak valid");
                }
            }
        }

        foreach (['question_number', 'display_order'] as $field) {
            if (! is_int($question[$field] ?? null) || $question[$field] < 1) {
                $this->fail("{$field} question {$label} tidak valid");
            }
        }

        if (! is_string($question['prompt'] ?? null) || trim($question['prompt']) === '') {
            $this->fail("prompt question {$label} kosong");
        }

        if (($question['explanation'] ?? null) !== null
            && ! is_string($question['explanation'])) {
            $this->fail("explanation question {$label} tidak valid");
        }

        foreach (['source', 'provenance', 'scoring', 'media', 'metadata'] as $field) {
            if (! is_array($question[$field] ?? null)) {
                $this->fail("{$field} question {$label} harus object");
            }
        }

        $promptMedia = $question['media']['prompt_ref'] ?? null;

        if ($promptMedia !== null && ! is_string($promptMedia)) {
            $this->fail("prompt media reference question {$label} tidak valid");
        }

        foreach (['owner', 'origin'] as $field) {
            if (! is_string($question['provenance'][$field] ?? null)
                || trim($question['provenance'][$field]) === '') {
                $this->fail("provenance question {$label} tidak lengkap");
            }
        }

        if (! is_string($question['source']['reference'] ?? null)
            || trim($question['source']['reference']) === '') {
            $this->fail("source question {$label} tidak lengkap");
        }

        $this->assertPublicMetadataSafe($question['metadata']['public'] ?? [], $label);
    }

    private function validateScoringAndOptions(
        array $question,
        string $label,
        array $media,
        bool $finalized,
    ): int {
        $type = $question['answer_type'];
        $scoring = $question['scoring'];
        $options = $question['options'] ?? null;
        $maxScore = $scoring['max_score'] ?? null;

        if ($type === IstAnswerType::NUMERIC) {
            if (! is_array($options) || $options !== [] || $maxScore !== 1
                || ! $this->isCanonicalDecimalCompatible($scoring['canonical_answer'] ?? null)) {
                $this->fail("kontrak numeric question {$label} tidak valid");
            }

            if (array_key_exists('accepted_answers', $scoring)) {
                $this->fail("numeric question {$label} tidak mendukung multi-key");
            }

            if (! $finalized
                && (! is_string($scoring['canonical_answer'] ?? null)
                    || preg_match('/\A(?:0|[1-9][0-9]*)\z/', $scoring['canonical_answer']) !== 1)) {
                $this->fail("canonical numeric staging question {$label} harus digit nonnegatif tanpa leading zero");
            }

            return 0;
        }

        if (! is_array($options) || count($options) !== 5) {
            $this->fail("choice question {$label} harus tepat lima opsi");
        }

        $keys = [];
        $orders = [];
        $correct = 0;
        $scoreThree = 0;
        $partial = 0;
        $weightedScores = [];

        foreach ($options as $index => $option) {
            if (! is_array($option)) {
                $this->fail("option {$label}/{$index} harus object");
            }

            $key = $option['key'] ?? null;
            $order = $option['display_order'] ?? null;

            if (! in_array($key, ['A', 'B', 'C', 'D', 'E'], true)
                || isset($keys[$key]) || ! is_int($order) || $order < 1 || isset($orders[$order])
                || ! is_bool($option['correct'] ?? null) || ! is_int($option['score'] ?? null)) {
                $this->fail("option {$label}/{$index} tidak valid atau duplikat");
            }

            $keys[$key] = true;
            $orders[$order] = true;
            $correct += $option['correct'] ? 1 : 0;

            if ($type === IstAnswerType::SINGLE_CHOICE_WEIGHTED) {
                if ($option['score'] < 0 || $option['score'] > 3
                    || (($option['score'] === 3) !== $option['correct'])) {
                    $this->fail("score GE option {$label}/{$key} tidak valid");
                }

                $scoreThree += $option['score'] === 3 ? 1 : 0;
                $partial += $option['score'] >= 1 && $option['score'] <= 2 ? 1 : 0;
                $weightedScores[] = $option['score'];
            }

            $mediaReference = $option['media_ref'] ?? null;

            if ($type === IstAnswerType::IMAGE_CHOICE) {
                if (! is_string($mediaReference) || ! isset($media[$mediaReference])) {
                    $this->fail("media option image choice {$label}/{$key} tidak tersedia");
                }
            } elseif ($mediaReference !== null) {
                $this->fail("media option hanya didukung image choice pada {$label}/{$key}");
            }

            if ($type !== IstAnswerType::IMAGE_CHOICE
                && (! is_string($option['text'] ?? null) || trim($option['text']) === '')) {
                $this->fail("teks option {$label}/{$key} kosong");
            }
        }

        $sortedKeys = array_keys($keys);
        $sortedOrders = array_keys($orders);
        sort($sortedKeys, SORT_STRING);
        sort($sortedOrders, SORT_NUMERIC);

        if ($sortedKeys !== ['A', 'B', 'C', 'D', 'E'] || $sortedOrders !== range(1, 5) || $correct !== 1) {
            $this->fail("choice question {$label} harus mempunyai opsi A-E dan satu correct");
        }

        if ($type === IstAnswerType::SINGLE_CHOICE_WEIGHTED) {
            if ($maxScore !== 3 || $scoreThree !== 1 || $partial < 1
                || ! is_string($scoring['rationale'] ?? null)
                || trim($scoring['rationale']) === '') {
                $this->fail("kontrak weighted GE question {$label} tidak valid");
            }
        }

        return 5;
    }

    private function validateStagingAggregate(
        array $manifest,
        array $subtests,
        array $media,
        int $optionTotal,
        array $checksums,
    ): void {
        if ($optionTotal !== 435 || count($media) !== 144) {
            $this->fail("staging harus mempunyai 435 opsi dan 144 media, ditemukan {$optionTotal}/".count($media));
        }

        $answerTypeCounts = array_fill_keys([
            IstAnswerType::SINGLE_CHOICE,
            IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            IstAnswerType::NUMERIC,
            IstAnswerType::IMAGE_CHOICE,
        ], 0);
        $expectedMaxScores = [
            'SE' => 23,
            'WA' => 23,
            'AN' => 23,
            'GE' => 60,
            'RA' => 23,
            'ZR' => 23,
            'FA' => 10,
            'WU' => 23,
            'ME' => 23,
        ];
        $questionIndex = [];

        foreach ($subtests as $code => $payload) {
            foreach ($payload['questions'] as $question) {
                $questionIndex[$question['logical_id']] = [
                    'subtest_code' => $code,
                    'prompt_media_ref' => $question['media']['prompt_ref'] ?? null,
                    'option_media_refs' => array_column(
                        $question['options'] ?? [],
                        'media_ref',
                        'key',
                    ),
                ];

                if ($question['kind'] === IstQuestion::KIND_SCORED) {
                    $answerTypeCounts[$question['answer_type']]++;
                }
            }

            $manifestEntry = collect($manifest['subtests'])->firstWhere('code', $code);
            if (! is_array($manifestEntry) || ($manifestEntry['max_score'] ?? null) !== $expectedMaxScores[$code]) {
                $this->fail("max score staging subtest {$code} tidak valid");
            }
        }

        foreach ($media as $record) {
            $questionId = $record['question_logical_id'] ?? null;
            $role = $record['role'] ?? null;
            $optionCode = $record['option_code'] ?? null;
            $question = is_string($questionId) ? ($questionIndex[$questionId] ?? null) : null;

            if (! is_array($question)
                || ($record['subtest_code'] ?? null) !== $question['subtest_code']) {
                $this->fail("relasi question media staging tidak valid: {$record['logical_id']}");
            }

            if ($role === 'option') {
                if (! is_string($optionCode)
                    || ($question['option_media_refs'][$optionCode] ?? null) !== $record['logical_id']) {
                    $this->fail("relasi option media staging tidak valid: {$record['logical_id']}");
                }
            } elseif (! in_array($role, ['prompt', 'reference'], true)
                || $optionCode !== null
                || $question['prompt_media_ref'] !== $record['logical_id']) {
                $this->fail("role media staging tidak valid: {$record['logical_id']}");
            }
        }

        $expectedAnswerTypeCounts = [
            IstAnswerType::SINGLE_CHOICE => 48,
            IstAnswerType::SINGLE_CHOICE_WEIGHTED => 10,
            IstAnswerType::NUMERIC => 24,
            IstAnswerType::IMAGE_CHOICE => 22,
        ];

        if ($answerTypeCounts !== $expectedAnswerTypeCounts
            || ($manifest['answer_type_counts_scored'] ?? null) !== $expectedAnswerTypeCounts) {
            $this->fail('count answer type scored staging tidak tepat');
        }

        if (($manifest['subtest_percentage_formula'] ?? null) !== 'awarded_score / max_subtest_score * 100'
            || ($manifest['total_internal_score_method'] ?? null) !== 'mean_of_four_domain_scores') {
            $this->fail('kontrak agregasi skor staging tidak tepat');
        }

        $this->validateMeStaging($subtests['ME']);

        $subtestHashes = [];
        foreach ($manifest['subtests'] as $entry) {
            $file = $entry['file'];
            $hash = $checksums['files'][$file] ?? null;
            if (! is_string($hash)) {
                $this->fail("checksum subtest staging tidak tersedia: {$file}");
            }
            $subtestHashes[$file] = $hash;
        }
        ksort($subtestHashes, SORT_STRING);
        $contentFingerprint = hash('sha256', implode("\n", array_map(
            static fn (string $path, string $hash): string => "{$path}:{$hash}",
            array_keys($subtestHashes),
            array_values($subtestHashes),
        )));

        $mediaParts = array_map(
            static fn (array $record): string => $record['logical_id'].':'.$record['sha256'],
            array_values($media),
        );
        sort($mediaParts, SORT_STRING);
        $mediaAggregate = hash('sha256', implode("\n", $mediaParts));
        $disclaimerFingerprint = hash('sha256', $manifest['result_disclaimer']);

        if (($manifest['content_fingerprint'] ?? null) !== $contentFingerprint
            || ($manifest['media_checksum_aggregate'] ?? null) !== $mediaAggregate
            || ($manifest['disclaimer_fingerprint'] ?? null) !== $disclaimerFingerprint) {
            $this->fail('aggregate fingerprint staging tidak cocok');
        }
    }

    private function validateMeStaging(array $payload): void
    {
        $memorization = $payload['memorization'] ?? null;

        if (! is_array($memorization)
            || ($memorization['model'] ?? null) !== 'initial_letter_to_category'
            || ($memorization['group_count'] ?? null) !== 5
            || ($memorization['words_per_group'] ?? null) !== 5
            || ($memorization['total_words'] ?? null) !== 25
            || ($memorization['unique_initials_required'] ?? null) !== true
            || ! is_array($memorization['groups'] ?? null)
            || count($memorization['groups']) !== 5) {
            $this->fail('kontrak memorization ME staging tidak lengkap');
        }

        $groupsByKey = [];
        $wordsByInitial = [];
        $allWords = [];
        $testedQuestionIds = [];

        foreach ($memorization['groups'] as $groupIndex => $group) {
            if (! is_array($group)
                || ! is_string($group['key'] ?? null)
                || ! is_string($group['name'] ?? null)
                || ! is_int($group['display_order'] ?? null)
                || ! is_array($group['words'] ?? null)
                || count($group['words']) !== 5) {
                $this->fail('record group ME staging tidak valid');
            }

            $groupKey = strtoupper(trim($group['key']));
            $groupName = trim($group['name']);

            if (! in_array($groupKey, ['A', 'B', 'C', 'D', 'E'], true)
                || $groupName === ''
                || isset($groupsByKey[$groupKey])) {
                $this->fail("group ME staging invalid atau duplicate: {$groupKey}");
            }

            $groupsByKey[$groupKey] = [
                'key' => $groupKey,
                'name' => $groupName,
            ];

            foreach ($group['words'] as $word) {
                if (! is_array($word)
                    || ! is_string($word['word'] ?? null)
                    || ! is_string($word['initial'] ?? null)
                    || ! is_bool($word['tested'] ?? null)) {
                    $this->fail('record word ME staging tidak valid');
                }

                $value = trim($word['word']);
                $initial = strtoupper(trim($word['initial']));

                if ($value === '' || mb_strlen($initial) !== 1) {
                    $this->fail('word atau initial ME staging invalid');
                }

                $actualInitial = mb_strtoupper(
                    mb_substr($value, 0, 1, 'UTF-8'),
                    'UTF-8'
                );

                if ($actualInitial !== $initial) {
                    $this->fail(
                        "initial ME staging tidak cocok untuk kata {$value}"
                    );
                }

                $normalizedWord = mb_strtolower($value);

                if (isset($allWords[$normalizedWord])) {
                    $this->fail(
                        "kata ME staging berulang: {$value}"
                    );
                }

                if (isset($wordsByInitial[$initial])) {
                    $this->fail(
                        "huruf awal ME staging berulang: {$initial}"
                    );
                }

                $allWords[$normalizedWord] = true;

                $wordsByInitial[$initial] = [
                    'word' => $value,
                    'group_key' => $groupKey,
                    'group_name' => $groupName,
                    'tested' => $word['tested'],
                    'question_logical_id' =>
                        $word['question_logical_id'] ?? null,
                ];

                if ($word['tested']) {
                    $questionLogicalId =
                        $word['question_logical_id'] ?? null;

                    if (! is_string($questionLogicalId)
                        || ! preg_match(
                            '/\AME-S-\d{3}\z/',
                            $questionLogicalId
                        )) {
                        $this->fail(
                            "mapping tested word ME staging invalid: {$value}"
                        );
                    }

                    if (isset($testedQuestionIds[$questionLogicalId])) {
                        $this->fail(
                            "question target ME staging duplicate: {$questionLogicalId}"
                        );
                    }

                    $testedQuestionIds[$questionLogicalId] = true;
                }
            }
        }

        if (array_keys($groupsByKey) !== ['A', 'B', 'C', 'D', 'E']
            || count($allWords) !== 25
            || count($wordsByInitial) !== 25
            || count($testedQuestionIds) !== 12) {
            $this->fail(
                'distribusi group/word/tested ME staging tidak valid'
            );
        }

        $actualInitials = array_keys($wordsByInitial);
        sort($actualInitials, SORT_STRING);

        if ($actualInitials !== [
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'J', 'K',
            'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U',
            'V', 'W', 'X', 'Y', 'Z',
        ]) {
            $this->fail('set huruf awal ME staging tidak sesuai kontrak final');
        }

        try {
            $participantMemory = json_decode(
                $payload['memorization_content'],
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable) {
            $this->fail(
                'memorization_content ME staging bukan JSON aman'
            );
        }

        if (! is_array($participantMemory)
            || array_keys($participantMemory) !== ['groups']
            || ! is_array($participantMemory['groups'])
            || count($participantMemory['groups']) !== 5) {
            $this->fail(
                'participant memorization ME staging tidak berisi tepat 5 group'
            );
        }

        foreach ($participantMemory['groups'] as $index => $group) {
            if (! is_array($group)
                || array_keys($group) !== [
                    'key',
                    'name',
                    'words',
                    'display_order',
                ]
                || ! is_string($group['key'])
                || ! is_string($group['name'])
                || ! is_array($group['words'])
                || count($group['words']) !== 5
                || ! is_int($group['display_order'])) {
                $this->fail(
                    'participant memorization group memuat field sensitif atau tipe invalid'
                );
            }

            $expectedKey = chr(ord('A') + $index);

            if ($group['key'] !== $expectedKey
                || ! isset($groupsByKey[$group['key']])
                || $group['name'] !== $groupsByKey[$group['key']]['name']) {
                $this->fail(
                    "participant group ME staging tidak sinkron: {$group['key']}"
                );
            }

            foreach ($group['words'] as $word) {
                if (! is_string($word) || trim($word) === '') {
                    $this->fail(
                        'participant word ME staging invalid'
                    );
                }

                $initial = mb_strtoupper(
                    mb_substr(trim($word), 0, 1, 'UTF-8'),
                    'UTF-8'
                );

                if (! isset($wordsByInitial[$initial])
                    || $wordsByInitial[$initial]['word'] !== $word
                    || $wordsByInitial[$initial]['group_key'] !== $group['key']) {
                    $this->fail(
                        "participant word ME staging tidak sinkron: {$word}"
                    );
                }
            }
        }

        $difficulty = [
            'easy' => 0,
            'medium' => 0,
            'hard' => 0,
        ];

        $questionTargets = [];
        $exampleCount = 0;

        foreach ($payload['questions'] as $question) {
            if ($question['kind'] !== IstQuestion::KIND_SCORED) {
                if ($question['kind'] === IstQuestion::KIND_EXAMPLE) {
                    $exampleCount++;
                    $this->validateMeExampleStaging($question, $groupsByKey);
                }

                continue;
            }

            $logicalId = $question['logical_id'] ?? null;

            if (! is_string($logicalId)
                || ! isset($testedQuestionIds[$logicalId])) {
                $this->fail(
                    "mapping question ME staging invalid: {$logicalId}"
                );
            }

            $internal = $question['metadata']['internal'] ?? null;

            if (! is_array($internal)
                || ($internal['memory_model'] ?? null)
                    !== 'initial_letter_to_category'
                || ! is_string($internal['target_initial'] ?? null)
                || ! is_string($internal['target_word'] ?? null)
                || ! is_string($internal['target_category_key'] ?? null)
                || ! is_string($internal['target_category_name'] ?? null)) {
                $this->fail(
                    "metadata internal ME staging invalid: {$logicalId}"
                );
            }

            $initial = strtoupper(
                trim($internal['target_initial'])
            );

            if (! isset($wordsByInitial[$initial])) {
                $this->fail(
                    "target initial ME staging tidak ditemukan: {$initial}"
                );
            }

            $target = $wordsByInitial[$initial];

            $expectedPrompt =
                "Kata yang mempunyai huruf permulaan {$initial} termasuk kelompok …";

            if (($question['prompt'] ?? null) !== $expectedPrompt
                || mb_stripos(
                    (string) ($question['prompt'] ?? ''),
                    (string) $internal['target_word'],
                    0,
                    'UTF-8',
                ) !== false) {
                $this->fail(
                    "participant prompt ME staging tidak aman: {$logicalId}"
                );
            }

            if ($target['word'] !== $internal['target_word']
                || $target['group_key']
                    !== $internal['target_category_key']
                || $target['group_name']
                    !== $internal['target_category_name']
                || $target['question_logical_id'] !== $logicalId) {
                $this->fail(
                    "target ME staging tidak sinkron: {$logicalId}"
                );
            }

            if (isset($questionTargets[$initial])) {
                $this->fail(
                    "target initial ME staging dipakai ulang: {$initial}"
                );
            }

            $questionTargets[$initial] = true;

            $difficultyTarget =
                $question['difficulty_target'] ?? null;

            if (! isset($difficulty[$difficultyTarget])) {
                $this->fail(
                    "difficulty ME staging invalid: {$logicalId}"
                );
            }

            $difficulty[$difficultyTarget]++;

            if (! is_array($question['options'] ?? null)
                || count($question['options']) !== 5) {
                $this->fail(
                    "jumlah opsi ME staging invalid: {$logicalId}"
                );
            }

            $correct = 0;

            foreach ($question['options'] as $index => $option) {
                if (! is_array($option)
                    || ! is_string($option['key'] ?? null)
                    || ! is_string($option['text'] ?? null)
                    || ! is_bool($option['correct'] ?? null)) {
                    $this->fail(
                        "opsi ME staging invalid: {$logicalId}"
                    );
                }

                $expectedKey = chr(ord('A') + $index);

                if ($option['key'] !== $expectedKey
                    || ! isset($groupsByKey[$option['key']])
                    || $option['text']
                        !== $groupsByKey[$option['key']]['name']) {
                    $this->fail(
                        "opsi kategori ME staging tidak sinkron: {$logicalId}"
                    );
                }

                if ($option['correct']) {
                    $correct++;

                    if ($option['key'] !== $target['group_key']
                        || ($option['score'] ?? null) !== 1) {
                        $this->fail(
                            "jawaban benar ME staging tidak sinkron: {$logicalId}"
                        );
                    }
                } elseif (($option['score'] ?? null) !== 0) {
                    $this->fail(
                        "skor distractor ME staging invalid: {$logicalId}"
                    );
                }
            }

            if ($correct !== 1) {
                $this->fail(
                    "ME staging harus memiliki tepat satu jawaban benar: {$logicalId}"
                );
            }
        }

        if (count($questionTargets) !== 12) {
            $this->fail(
                'jumlah target question ME staging tidak tepat'
            );
        }

        if ($exampleCount !== 1) {
            $this->fail('jumlah example ME staging harus tepat satu');
        }

        if ($difficulty !== [
            'easy' => 4,
            'medium' => 5,
            'hard' => 3,
        ]) {
            $this->fail(
                'distribusi difficulty ME staging tidak tepat'
            );
        }

        $weightedMaximum = $difficulty['easy']
            + ($difficulty['medium'] * 2)
            + ($difficulty['hard'] * 3);

        if ($weightedMaximum !== 23) {
            $this->fail('weighted maximum ME staging harus 23');
        }
    }

    private function validateMeExampleStaging(array $question, array $groupsByKey): void
    {
        $internal = $question['metadata']['internal'] ?? null;

        if (($question['logical_id'] ?? null) !== 'ME-E-001'
            || ($question['prompt'] ?? null)
                !== 'Kata yang mempunyai huruf permulaan P termasuk kelompok …'
            || ! is_array($internal)
            || ($internal['memory_model'] ?? null)
                !== 'initial_letter_to_category'
            || ($internal['example_initial'] ?? null) !== 'P'
            || ($internal['example_word'] ?? null) !== 'Pahat'
            || ($internal['example_category_key'] ?? null) !== 'B'
            || ($internal['example_category_name'] ?? null) !== 'Peralatan'
            || mb_stripos((string) ($question['prompt'] ?? ''), 'Pahat', 0, 'UTF-8') !== false
            || mb_stripos((string) ($question['explanation'] ?? ''), 'Pahat', 0, 'UTF-8') !== false) {
            $this->fail('kontrak participant example ME staging tidak aman');
        }

        if (! is_array($question['options'] ?? null)
            || count($question['options']) !== 5) {
            $this->fail('example ME staging harus mempunyai tepat lima opsi');
        }

        $correct = 0;

        foreach ($question['options'] as $index => $option) {
            $expectedKey = chr(ord('A') + $index);

            if (($option['key'] ?? null) !== $expectedKey
                || ($option['text'] ?? null) !== ($groupsByKey[$expectedKey]['name'] ?? null)) {
                $this->fail('opsi example ME staging tidak sinkron');
            }

            if (($option['correct'] ?? false) === true) {
                $correct++;

                if ($expectedKey !== 'B' || ($option['score'] ?? null) !== 1) {
                    $this->fail('answer key example ME staging harus B');
                }
            } elseif (($option['score'] ?? null) !== 0) {
                $this->fail('skor distractor example ME staging invalid');
            }
        }

        if ($correct !== 1) {
            $this->fail('example ME staging harus mempunyai satu jawaban benar');
        }
    }

    private function assertPublicMetadataSafe(mixed $metadata, string $label): void
    {
        if (! is_array($metadata)) {
            $this->fail("metadata.public question {$label} harus object");
        }

        $forbidden = ['answer_key', 'correct', 'is_correct', 'score', 'score_value', 'weight', 'weights', 'scoring'];

        $walk = function (array $values) use (&$walk, $forbidden, $label): void {
            foreach ($values as $key => $value) {
                if (in_array(strtolower((string) $key), $forbidden, true)) {
                    $this->fail("metadata publik question {$label} memuat scoring/key");
                }

                if (is_array($value)) {
                    $walk($value);
                }
            }
        };

        $walk($metadata);
    }

    private function assertNoForbiddenMarkers(array $payload): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($encoded)) {
            $this->fail('dataset tidak dapat diperiksa terhadap marker terlarang');
        }

        foreach (['[DEV:', 'ist-development', '[FINAL-TEMPLATE-NOT-ACTIVE]'] as $marker) {
            if (stripos($encoded, $marker) !== false) {
                $this->fail("dataset memuat marker terlarang {$marker}");
            }
        }
    }

    private function assertNoForeignFiles(string $directory, array $declared): void
    {
        $declared = array_values(array_unique($declared));
        sort($declared, SORT_STRING);
        $actual = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                $this->fail('symlink tidak diizinkan dalam dataset final');
            }

            if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
                $actual[] = $this->declaredPath($relative, 'actual file');
            }
        }

        sort($actual, SORT_STRING);

        if ($actual !== $declared) {
            $this->fail('dataset mempunyai file tambahan atau file deklarasi yang hilang');
        }
    }

    private function canonicalDirectory(string $directory): string
    {
        if ($directory === '' || is_link($directory) || ! is_dir($directory)) {
            $this->fail('directory dataset tidak tersedia atau berupa symlink');
        }

        $real = realpath($directory);

        if (! is_string($real) || $real === '') {
            $this->fail('directory dataset tidak dapat diresolusikan');
        }

        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function declaredPath(mixed $value, string $label): string
    {
        return $this->mediaValidator->normalizeRelativePath($value, $label);
    }

    private function readJson(string $path, string $label): array
    {
        if (is_link($path) || ! is_file($path)) {
            $this->fail("file {$label} tidak tersedia atau berupa symlink");
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->fail("JSON {$label} tidak valid");
        }

        if (! is_array($decoded)) {
            $this->fail("JSON {$label} harus berupa object");
        }

        return $decoded;
    }

    private function isCanonicalDecimalCompatible(mixed $value): bool
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            return false;
        }

        $number = trim((string) $value);

        if (! preg_match('/\A[+-]?(?:\d+(?:\.\d{0,6})?|\.\d{1,6})\z/', $number)) {
            return false;
        }

        $unsigned = ltrim($number, '+-');
        [$integer] = array_pad(explode('.', $unsigned, 2), 1, '');
        $integer = ltrim($integer, '0');

        return strlen($integer === '' ? '0' : $integer) <= 14;
    }

    private function isPlaceholder(string $value): bool
    {
        return preg_match('/placeholder|replace[-_ ]?me|\btodo\b|\btbd\b|final-template-not-active/i', $value) === 1;
    }

    private function fail(string $reason): never
    {
        throw InvalidIstFinalQuestionDatasetException::because($reason);
    }
}
