<?php

namespace App\Services\Ist\Import;

use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Models\Ist\IstQuestion;
use App\Support\Ist\IstAnswerType;
use App\Support\Ist\IstSubtestCatalog;
use DOMDocument;
use DOMElement;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class IstQuestionDatasetValidator
{
    private const SHA256_PATTERN = '/\A[a-f0-9]{64}\z/';

    public function validate(?string $datasetDirectory = null): array
    {
        $directory = rtrim($datasetDirectory ?? (string) config('ist.development_dataset_path'), DIRECTORY_SEPARATOR);
        $manifestPath = $directory.DIRECTORY_SEPARATOR.'manifest.json';
        $manifest = $this->readJson($manifestPath, 'manifest.json');
        $this->validateManifestIdentity($manifest);

        $declaredFiles = ['manifest.json'];
        $subtestPayloads = [];
        $catalog = array_column(IstSubtestCatalog::all(), null, 'code');
        $expectedCodes = array_keys($catalog);

        if (array_keys($manifest['subtests'] ?? []) !== $expectedCodes) {
            $this->fail('urutan/katalog subtes manifest harus tepat SE, WA, AN, GE, RA, ZR, FA, WU, ME');
        }

        $scoredTotal = 0;
        $exampleTotal = 0;
        $optionTotal = 0;
        $mediaTargets = [];

        foreach ($manifest['media'] ?? [] as $index => $media) {
            $file = $this->validateRelativeFileEntry($directory, $media, "media[{$index}]");
            $declaredFiles[] = $file;
            $target = $media['target_path'] ?? null;
            if (! is_string($target) || ! preg_match('/\Aist-development\/v1\/[a-z0-9-]+\.svg\z/', $target)) {
                $this->fail("target media #{$index} tidak aman");
            }
            if (isset($mediaTargets[$target])) {
                $this->fail("target media duplikat: {$target}");
            }
            $this->validateSvg($directory.DIRECTORY_SEPARATOR.$file, $file);
            $mediaTargets[$target] = $media;
        }

        if (count($mediaTargets) !== 6) {
            $this->fail('manifest harus mendeklarasikan tepat 6 media SVG');
        }

        foreach ($expectedCodes as $code) {
            $entry = $manifest['subtests'][$code];
            $file = $this->validateRelativeFileEntry($directory, $entry, "subtest {$code}");
            $declaredFiles[] = $file;
            $payload = $this->readJson($directory.DIRECTORY_SEPARATOR.$file, $file);
            [$scored, $examples, $options] = $this->validateSubtest(
                $payload,
                $code,
                $catalog[$code],
                $manifest,
                array_keys($mediaTargets),
            );

            if (($entry['scored_question_count'] ?? null) !== $scored
                || ($entry['example_question_count'] ?? null) !== $examples) {
                $this->fail("jumlah soal {$code} berbeda dari manifest");
            }

            $scoredTotal += $scored;
            $exampleTotal += $examples;
            $optionTotal += $options;
            $subtestPayloads[$code] = $payload;
        }

        $expectedEntry = $manifest['expected_results'] ?? [];
        $expectedFile = $this->validateRelativeFileEntry($directory, $expectedEntry, 'expected-results');
        $declaredFiles[] = $expectedFile;
        $expected = $this->readJson($directory.DIRECTORY_SEPARATOR.$expectedFile, $expectedFile);
        $this->validateExpectedResults($expected, $manifest, $catalog);
        $this->assertNoForeignFiles($directory, $declaredFiles);

        if ($scoredTotal !== 104 || $exampleTotal !== 9 || $optionTotal !== 435) {
            $this->fail("total dataset tidak tepat (scored={$scoredTotal}, example={$exampleTotal}, options={$optionTotal})");
        }

        if (($manifest['total_scored_question_count'] ?? null) !== 104
            || ($manifest['total_example_question_count'] ?? null) !== 9) {
            $this->fail('total pada manifest tidak tepat');
        }

        return [
            'directory' => $directory,
            'manifest' => $manifest,
            'subtests' => $subtestPayloads,
            'media' => array_values($manifest['media']),
            'counts' => [
                'subtests' => 9,
                'scored_questions' => $scoredTotal,
                'example_questions' => $exampleTotal,
                'options' => $optionTotal,
                'media' => count($mediaTargets),
                'expected_answers' => 104,
            ],
        ];
    }

    private function validateManifestIdentity(array $manifest): void
    {
        $expected = [
            'schema_version' => 1,
            'dataset' => 'ist-development',
            'dataset_type' => 'development',
            'dataset_version' => 1,
            'record_version' => 900000001,
            'ownership_marker' => '[DEV:ist-development]',
        ];

        foreach ($expected as $key => $value) {
            if (($manifest[$key] ?? null) !== $value) {
                $this->fail("manifest.{$key} tidak valid");
            }
        }

        if (($manifest['environments'] ?? null) !== ['local', 'testing']) {
            $this->fail('environment manifest tidak valid');
        }
    }

    private function validateSubtest(
        array $payload,
        string $code,
        array $catalog,
        array $manifest,
        array $mediaTargets,
    ): array {
        if (($payload['schema_version'] ?? null) !== 1
            || ($payload['dataset'] ?? null) !== $manifest['dataset']
            || ($payload['dataset_version'] ?? null) !== $manifest['dataset_version']
            || ($payload['code'] ?? null) !== $code) {
            $this->fail("identitas file {$code} tidak valid");
        }

        $marker = $manifest['ownership_marker'];
        if (! is_string($payload['instruction_content'] ?? null)
            || ! str_starts_with($payload['instruction_content'], $marker)) {
            $this->fail("instruction_content {$code} tidak memiliki marker");
        }

        $memorization = $payload['memorization_content'] ?? null;
        if ($code === 'ME') {
            if (! is_string($memorization) || ! str_starts_with($memorization, $marker)) {
                $this->fail('memorization_content ME wajib memiliki marker');
            }
        } elseif ($memorization !== null) {
            $this->fail("memorization_content hanya boleh ada pada ME, ditemukan pada {$code}");
        }

        $questions = $payload['questions'] ?? null;
        if (! is_array($questions)) {
            $this->fail("questions {$code} harus array");
        }

        $numbers = [IstQuestion::KIND_SCORED => [], IstQuestion::KIND_EXAMPLE => []];
        $orders = [IstQuestion::KIND_SCORED => [], IstQuestion::KIND_EXAMPLE => []];
        $counts = [IstQuestion::KIND_SCORED => 0, IstQuestion::KIND_EXAMPLE => 0];
        $optionCount = 0;

        foreach ($questions as $index => $question) {
            $label = "{$code} question #{$index}";
            $kind = $question['kind'] ?? null;
            if (! isset($counts[$kind])) {
                $this->fail("{$label} kind tidak valid");
            }
            $number = $question['question_number'] ?? null;
            $order = $question['display_order'] ?? null;
            if (! is_int($number) || $number < 1 || isset($numbers[$kind][$number])) {
                $this->fail("{$label} question_number tidak valid/duplikat");
            }
            if (! is_int($order) || $order < 1 || isset($orders[$kind][$order])) {
                $this->fail("{$label} display_order tidak valid/duplikat");
            }
            $numbers[$kind][$number] = true;
            $orders[$kind][$order] = true;
            $counts[$kind]++;

            if (($question['answer_type'] ?? null) !== $catalog['default_answer_type']) {
                $this->fail("{$label} answer_type tidak sesuai katalog");
            }
            if (($question['source_version'] ?? null) !== $manifest['record_version']) {
                $this->fail("{$label} source_version tidak valid");
            }
            if (($question['is_active'] ?? null) !== true
                || ! is_string($question['prompt'] ?? null)
                || ! str_starts_with($question['prompt'], $marker)) {
                $this->fail("{$label} marker/status tidak valid");
            }

            $explanation = $question['example_explanation'] ?? null;
            if ($kind === IstQuestion::KIND_EXAMPLE
                && (! is_string($explanation) || ! str_starts_with($explanation, $marker))) {
                $this->fail("{$label} example_explanation wajib memiliki marker");
            }
            if ($kind === IstQuestion::KIND_SCORED && $explanation !== null) {
                $this->fail("{$label} scored tidak boleh mempunyai example_explanation");
            }

            $this->validateImageMetadata($question, $mediaTargets, "{$label} image");
            $options = $question['options'] ?? null;
            $type = $question['answer_type'];

            if ($type === IstAnswerType::NUMERIC) {
                if (! is_array($options) || $options !== []) {
                    $this->fail("{$label} numeric tidak boleh mempunyai opsi");
                }
                if (! $this->isCanonicalDecimalCompatible($question['numeric_answer_key'] ?? null)) {
                    $this->fail("{$label} numeric_answer_key tidak kompatibel DECIMAL(20,6)");
                }
                if (! $this->sameNumber($question['max_score'] ?? null, 1)) {
                    $this->fail("{$label} max_score numeric harus 1");
                }

                continue;
            }

            if (($question['numeric_answer_key'] ?? null) !== null || ! is_array($options) || count($options) !== 5) {
                $this->fail("{$label} choice harus tepat 5 opsi dan tanpa numeric key");
            }

            $keys = [];
            $optionOrders = [];
            $correct = 0;
            $scoreThree = 0;
            foreach ($options as $optionIndex => $option) {
                $optionLabel = "{$label} option #{$optionIndex}";
                $key = $option['option_key'] ?? null;
                $optionOrder = $option['display_order'] ?? null;
                if (! in_array($key, ['A', 'B', 'C', 'D', 'E'], true) || isset($keys[$key])) {
                    $this->fail("{$optionLabel} key tidak valid/duplikat");
                }
                if (! is_int($optionOrder) || $optionOrder < 1 || isset($optionOrders[$optionOrder])) {
                    $this->fail("{$optionLabel} display_order tidak valid/duplikat");
                }
                if (($option['is_active'] ?? null) !== true || ! is_bool($option['is_correct'] ?? null)) {
                    $this->fail("{$optionLabel} status tidak valid");
                }
                if ((! is_string($option['option_text'] ?? null) || trim($option['option_text']) === '')
                    && ($option['image_path'] ?? null) === null) {
                    $this->fail("{$optionLabel} harus mempunyai teks fallback atau gambar");
                }
                $keys[$key] = true;
                $optionOrders[$optionOrder] = true;
                $correct += $option['is_correct'] ? 1 : 0;
                $score = $option['score_value'] ?? null;
                $this->validateImageMetadata($option, $mediaTargets, "{$optionLabel} image");

                if ($type === IstAnswerType::SINGLE_CHOICE_WEIGHTED) {
                    if (! is_int($score) || $score < 0 || $score > 3) {
                        $this->fail("{$optionLabel} skor GE harus integer 0-3");
                    }
                    $scoreThree += $score === 3 ? 1 : 0;
                    if (($score === 3) !== $option['is_correct']) {
                        $this->fail("{$optionLabel} skor GE dan is_correct tidak konsisten");
                    }
                } elseif (! in_array($score, [0, 1], true) || (($score === 1) !== $option['is_correct'])) {
                    $this->fail("{$optionLabel} skor binary dan is_correct tidak konsisten");
                }
            }

            if ($correct !== 1 || ($type === IstAnswerType::SINGLE_CHOICE_WEIGHTED && $scoreThree !== 1)) {
                $this->fail("{$label} harus mempunyai tepat satu jawaban benar");
            }
            $expectedMax = $type === IstAnswerType::SINGLE_CHOICE_WEIGHTED ? 3 : 1;
            if (! $this->sameNumber($question['max_score'] ?? null, $expectedMax)) {
                $this->fail("{$label} max_score tidak tepat");
            }
            $optionCount += 5;
        }

        if ($counts[IstQuestion::KIND_SCORED] !== $catalog['question_count']
            || $counts[IstQuestion::KIND_EXAMPLE] !== 1) {
            $this->fail("jumlah scored/example {$code} tidak tepat");
        }

        return [$counts[IstQuestion::KIND_SCORED], $counts[IstQuestion::KIND_EXAMPLE], $optionCount];
    }

    private function validateExpectedResults(array $expected, array $manifest, array $catalog): void
    {
        if (($expected['schema_version'] ?? null) !== 1
            || ($expected['dataset'] ?? null) !== $manifest['dataset']
            || ($expected['dataset_type'] ?? null) !== 'development'
            || ($expected['metadata_only'] ?? null) !== true
            || ($expected['record_version'] ?? null) !== $manifest['record_version']) {
            $this->fail('metadata expected-results tidak valid');
        }

        $sheet = $expected['answer_sheet'] ?? null;
        if (! is_array($sheet) || array_keys($sheet) !== array_keys($catalog)) {
            $this->fail('answer_sheet expected-results tidak sesuai katalog');
        }

        $total = 0;
        foreach ($catalog as $code => $definition) {
            if (! is_array($sheet[$code]) || count($sheet[$code]) !== $definition['question_count']) {
                $this->fail("answer_sheet {$code} tidak lengkap");
            }
            $total += count($sheet[$code]);
        }
        if ($total !== 104) {
            $this->fail('answer_sheet harus berisi tepat 104 jawaban');
        }

        foreach ($sheet as $code => $answers) {
            $numbers = [];
            foreach ($answers as $answer) {
                $number = $answer['question_number'] ?? null;
                if (! is_int($number) || $number < 1 || $number > $catalog[$code]['question_count'] || isset($numbers[$number])) {
                    $this->fail("answer_sheet {$code} memiliki question_number tidak valid/duplikat");
                }
                $numbers[$number] = true;
                if (! is_numeric($answer['expected_score'] ?? null)
                    || ! in_array($answer['expected_outcome'] ?? null, ['correct', 'partial', 'wrong', 'blank'], true)) {
                    $this->fail("answer_sheet {$code} memiliki expected score/outcome tidak valid");
                }
            }
        }

        $allCorrect = $expected['expected_all_correct'] ?? null;
        if (! is_array($allCorrect)
            || ! $this->sameNumber($allCorrect['total_awarded_score'] ?? null, 124)
            || ! $this->sameNumber($allCorrect['total_max_score'] ?? null, 124)
            || ! $this->sameNumber($allCorrect['total_internal_score'] ?? null, 100)
            || ! is_array($allCorrect['subtests'] ?? null)
            || array_keys($allCorrect['subtests']) !== array_keys($catalog)) {
            $this->fail('expected_all_correct tidak valid');
        }

        $verification = $expected['verification_cases'] ?? null;
        if (! is_array($verification)
            || ($verification['numeric_canonical_equivalents'] ?? null) !== ['10', '10.0', '10.000000']
            || ($verification['ge_partial_scores'] ?? null) !== [1, 2]
            || ($verification['blank_score'] ?? null) !== 0) {
            $this->fail('verification_cases expected-results tidak valid');
        }
    }

    private function validateRelativeFileEntry(string $directory, mixed $entry, string $label): string
    {
        if (! is_array($entry) || ! is_string($entry['file'] ?? null)
            || str_contains($entry['file'], '..') || str_starts_with($entry['file'], '/')) {
            $this->fail("path {$label} tidak aman");
        }
        $checksum = $entry['sha256'] ?? null;
        if (! is_string($checksum) || ! preg_match(self::SHA256_PATTERN, $checksum)) {
            $this->fail("checksum {$label} kosong atau bukan SHA-256 lowercase");
        }
        $path = $directory.DIRECTORY_SEPARATOR.$entry['file'];
        if (! is_file($path)) {
            $this->fail("file {$label} hilang");
        }
        if (! hash_equals($checksum, hash_file('sha256', $path))) {
            $this->fail("checksum {$label} tidak cocok");
        }

        return $entry['file'];
    }

    private function assertNoForeignFiles(string $directory, array $declared): void
    {
        sort($declared);
        $actual = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $actual[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($actual);
        if ($actual !== $declared) {
            $this->fail('dataset mengandung file asing atau file deklarasi ganda');
        }
    }

    private function validateImageMetadata(array $item, array $mediaTargets, string $label): void
    {
        $disk = $item['image_disk'] ?? null;
        $path = $item['image_path'] ?? null;
        $alt = $item['image_alt'] ?? null;
        if ($disk === null && $path === null && $alt === null) {
            return;
        }
        if ($disk !== 'public' || ! is_string($path) || ! in_array($path, $mediaTargets, true)
            || ! is_string($alt) || trim($alt) === '' || ! str_contains(strtoupper($alt), 'DEV')) {
            $this->fail("{$label} harus lengkap, berlabel DEV, dan menunjuk media manifest");
        }
    }

    private function validateSvg(string $path, string $label): void
    {
        $contents = file_get_contents($path);
        if ($contents === false || ! str_contains(strtoupper($contents), 'DEV')) {
            $this->fail("SVG {$label} tidak berlabel DEV");
        }
        if (preg_match('/(?:<\s*(?:script|foreignObject|image|use|iframe|object|embed)\b|\bon[a-z]+\s*=|javascript\s*:|data\s*:|xlink:href|\bhref\s*=)/i', $contents)) {
            $this->fail("SVG {$label} mengandung elemen/referensi terlarang");
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded || $document->documentElement?->localName !== 'svg') {
            $this->fail("SVG {$label} bukan XML SVG valid");
        }
        $allowed = ['svg', 'rect', 'text', 'g', 'title', 'desc'];
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && ! in_array($element->localName, $allowed, true)) {
                $this->fail("SVG {$label} memakai elemen {$element->localName} yang tidak diizinkan");
            }
            if ($element instanceof DOMElement) {
                foreach ($element->attributes as $attribute) {
                    $name = strtolower($attribute->nodeName);
                    $value = strtolower(trim($attribute->nodeValue));
                    if (str_starts_with($name, 'on') || in_array($name, ['href', 'xlink:href'], true)
                        || (preg_match('/\A(?:https?:|javascript:|data:|\/\/)/', $value)
                            && ! ($name === 'xmlns' && $value === 'http://www.w3.org/2000/svg'))) {
                        $this->fail("SVG {$label} memakai atribut/referensi terlarang");
                    }
                }
            }
        }
    }

    private function isCanonicalDecimalCompatible(mixed $value): bool
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            return false;
        }
        $text = (string) $value;
        if (! preg_match('/\A-?(?:0|[1-9]\d{0,13})(?:\.\d{1,6})?\z/', $text)) {
            return false;
        }

        return is_finite((float) $text);
    }

    private function sameNumber(mixed $left, int $right): bool
    {
        return is_numeric($left) && (float) $left === (float) $right;
    }

    private function readJson(string $path, string $label): array
    {
        if (! is_file($path)) {
            $this->fail("{$label} hilang");
        }
        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->fail("{$label} bukan JSON valid");
        }
        if (! is_array($decoded)) {
            $this->fail("{$label} harus JSON object");
        }

        return $decoded;
    }

    private function fail(string $reason): never
    {
        throw InvalidIstQuestionDatasetException::because($reason);
    }
}
