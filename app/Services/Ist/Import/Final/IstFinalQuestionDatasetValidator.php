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
        $this->logicalIds = [];
        $directory = $this->canonicalDirectory($datasetDirectory);
        $manifest = $this->readJson($directory.'/manifest.json', 'manifest.json');
        $this->validateManifest($manifest);

        $approvalsPath = $this->declaredPath($manifest['files']['approvals'] ?? null, 'approvals');
        $checksumsPath = $this->declaredPath($manifest['files']['checksums'] ?? null, 'checksums');
        $mediaMetadataPath = $this->declaredPath($manifest['files']['media_metadata'] ?? null, 'media metadata');
        $approvals = $this->readJson($directory.'/'.$approvalsPath, $approvalsPath);
        $checksums = $this->readJson($directory.'/'.$checksumsPath, $checksumsPath);
        $mediaPayload = $this->readJson($directory.'/'.$mediaMetadataPath, $mediaMetadataPath);
        $media = $this->mediaValidator->validate($directory, $manifest, $mediaPayload);

        $this->validateApprovalsAndFreeze($manifest, $approvals, $checksums);

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
            );
            $subtests[$code] = $payload;
            $scoredTotal += $scored;
            $exampleTotal += $examples;
            $optionTotal += $options;
        }

        if ($scoredTotal !== 104 || $exampleTotal !== 9) {
            $this->fail("total aktual harus 104 scored dan 9 example, ditemukan {$scoredTotal}/{$exampleTotal}");
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

    private function validateManifest(array $manifest): void
    {
        $expectedIdentity = [
            'schema_version' => 1,
            'dataset_type' => 'final',
            'instrument_identifier' => (string) config('ist.final_instrument_identifier', self::INSTRUMENT_IDENTIFIER),
            'product_name' => (string) config('ist.final_product_name', self::PRODUCT_NAME),
            'status' => 'frozen',
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
            || ($manifest['approval']['file'] ?? null) !== ($manifest['files']['approvals'] ?? null)) {
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
                'scored_question_count' => $definition['question_count'],
                'example_count' => 1,
                'duration_seconds' => $definition['duration_seconds'],
                'memorization_duration_seconds' => $definition['memorization_seconds'],
                'answering_duration_seconds' => $definition['answering_seconds'],
            ];

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

    private function validateApprovalsAndFreeze(array $manifest, array $approvals, array $checksums): void
    {
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

            $this->validateCommonQuestion($question, $label, $code, $catalog, $manifest);
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

            $optionCount += $this->validateScoringAndOptions($question, $label, $media);
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
            || ($question['review_status'] ?? null) !== 'approved'
            || ($question['source_version'] ?? null) !== $manifest['record_version']) {
            $this->fail("kontrak status/type/version question {$label} tidak valid");
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

    private function validateScoringAndOptions(array $question, string $label, array $media): int
    {
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

            return 0;
        }

        if (! is_array($options) || count($options) !== 5) {
            $this->fail("choice question {$label} harus tepat lima opsi");
        }

        $keys = [];
        $orders = [];
        $correct = 0;
        $scoreFour = 0;
        $partial = 0;

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
                if ($option['score'] < 0 || $option['score'] > 4
                    || (($option['score'] === 4) !== $option['correct'])) {
                    $this->fail("score GE option {$label}/{$key} tidak valid");
                }

                $scoreFour += $option['score'] === 4 ? 1 : 0;
                $partial += $option['score'] >= 1 && $option['score'] <= 3 ? 1 : 0;
            } elseif (! in_array($option['score'], [0, 1], true)
                || (($option['score'] === 1) !== $option['correct'])) {
                $this->fail("score binary option {$label}/{$key} tidak valid");
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
            if ($maxScore !== 4 || $scoreFour !== 1 || $partial < 1
                || ! is_string($scoring['rationale'] ?? null)
                || trim($scoring['rationale']) === '') {
                $this->fail("kontrak weighted GE question {$label} tidak valid");
            }
        } elseif ($maxScore !== 1) {
            $this->fail("max score binary question {$label} harus 1");
        }

        return 5;
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
