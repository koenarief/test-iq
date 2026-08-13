<?php

declare(strict_types=1);

/**
 * Build canonical WU media and records from the user-reviewed source traces.
 *
 * This generator is intentionally isolated from generate-wu-final.php. It
 * preserves the existing staging filenames consumed by the React adapter,
 * while replacing only WU content.
 */

$root = dirname(__DIR__);
$datasetDir = $root.'/database/data/ist-final-staging';
$previewDir = $root.'/docs/ist/content-drafts/wu-source-trace-preview';
$mediaDir = $datasetDir.'/media/wu';
$exampleDir = $mediaDir.'/examples';
$questionDir = $mediaDir.'/questions';
$optionDir = $mediaDir.'/options';

$wuPath = $datasetDir.'/wu.json';
$metadataPath = $datasetDir.'/media/metadata.json';
$manifestPath = $datasetDir.'/manifest.json';
$checksumsPath = $datasetDir.'/checksums.json';

$plan = [
    1 => ['source' => 137, 'key' => 'A', 'difficulty' => 'easy'],
    2 => ['source' => 138, 'key' => 'C', 'difficulty' => 'easy'],
    3 => ['source' => 139, 'key' => 'D', 'difficulty' => 'easy'],
    4 => ['source' => 140, 'key' => 'E', 'difficulty' => 'easy'],
    5 => ['source' => 141, 'key' => 'A', 'difficulty' => 'medium'],
    6 => ['source' => 142, 'key' => 'C', 'difficulty' => 'medium'],
    7 => ['source' => 143, 'key' => 'D', 'difficulty' => 'medium'],
    8 => ['source' => 144, 'key' => 'C', 'difficulty' => 'medium'],
    9 => ['source' => 145, 'key' => 'E', 'difficulty' => 'medium'],
    10 => ['source' => 146, 'key' => 'A', 'difficulty' => 'hard'],
    11 => ['source' => 147, 'key' => 'B', 'difficulty' => 'hard'],
    12 => ['source' => 148, 'key' => 'D', 'difficulty' => 'hard'],
];

$masterSources = [
    'A' => $previewDir.'/masters-v1/master-a.svg',
    'B' => $previewDir.'/masters-v1/master-b.svg',
    'C' => $previewDir.'/masters-v1/master-c.svg',
    'D' => $previewDir.'/masters-v1/master-d.svg',
    'E' => $previewDir.'/masters-v1/master-e.svg',
];

$targetSources = [];

foreach ($plan as $number => $item) {
    $source = $item['source'];
    $group = match (true) {
        $source <= 141 => 'targets-137-141-v1',
        $source <= 146 => 'targets-142-146-v1',
        default => 'targets-147-148-v1',
    };
    $targetSources[$number] = "{$previewDir}/{$group}/wu-{$source}-candidate.svg";
}

$exampleSource = $previewDir.'/example-v1/wu-example-candidate.svg';

function wuRead(string $path): string
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Tidak dapat membaca file WU: {$path}");
    }

    return $contents;
}

function wuReadJson(string $path): array
{
    return json_decode(wuRead($path), true, 512, JSON_THROW_ON_ERROR);
}

function wuWrite(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Tidak dapat menulis file WU: {$path}");
    }
}

function wuWriteJson(string $path, array $payload): void
{
    wuWrite(
        $path,
        json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        )."\n",
    );
}

function wuRuntimeSvg(string $source, string $role, string $sourceLabel): string
{
    $svg = wuRead($source);

    if (! str_contains($svg, 'width="240"')
        || ! str_contains($svg, 'height="180"')
        || ! str_contains($svg, 'viewBox="0 0 240 180"')) {
        throw new RuntimeException("Dimensi SVG sumber WU tidak valid: {$source}");
    }

    $replacement = sprintf(
        '<svg data-wu-role="%s" data-wu-source="%s" ',
        htmlspecialchars($role, ENT_QUOTES | ENT_XML1),
        htmlspecialchars($sourceLabel, ENT_QUOTES | ENT_XML1),
    );

    $result = preg_replace('/<svg\s+/', $replacement, $svg, 1);

    if (! is_string($result) || ! str_contains($result, 'data-wu-role=')) {
        throw new RuntimeException("Atribut runtime gagal ditambahkan: {$source}");
    }

    return $result;
}

foreach ([$exampleSource, ...array_values($masterSources), ...array_values($targetSources)] as $source) {
    if (! is_file($source)) {
        throw new RuntimeException("Source trace WU tidak ditemukan: {$source}");
    }
}

// Five identical master choices are reused by the example and all 12 items.
foreach ($masterSources as $key => $source) {
    $lower = strtolower($key);
    $svg = wuRuntimeSvg($source, 'master', "master-{$lower}");
    wuWrite("{$exampleDir}/wu-example-001-option-{$lower}.svg", $svg);

    foreach (array_keys($plan) as $number) {
        $local = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        wuWrite("{$optionDir}/wu-q{$local}-option-{$lower}.svg", $svg);
    }
}

wuWrite(
    "{$exampleDir}/wu-example-001-reference.svg",
    wuRuntimeSvg($exampleSource, 'target', 'source-example'),
);

foreach ($targetSources as $number => $source) {
    $local = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    wuWrite(
        "{$questionDir}/wu-q{$local}-reference.svg",
        wuRuntimeSvg($source, 'target', 'source-'.$plan[$number]['source']),
    );
}

$wu = wuReadJson($wuPath);
$wu['instruction_content'] = implode("\n", [
    'Perhatikan lima kubus acuan A–E.',
    'Setiap kubus memiliki susunan tanda yang berbeda pada keenam sisinya.',
    'Pada setiap soal hanya ditampilkan satu kubus target dalam kedudukan yang berbeda.',
    'Pilih kubus acuan A, B, C, D, atau E yang identik dengan kubus target setelah diputar secara legal.',
    'Kubus boleh diputar atau digulingkan dalam pikiran, tetapi tidak boleh dicerminkan.',
]);

$foundExample = false;
$foundScored = [];

foreach ($wu['questions'] as &$question) {
    if (($question['kind'] ?? null) === 'example') {
        $foundExample = true;
        $question['prompt'] = 'Perhatikan kubus target contoh. Pilih kubus acuan A–E yang identik setelah rotasi legal.';
        $question['explanation'] = 'Jawaban contoh adalah kubus A.';
        $question['difficulty_target'] = null;
        $question['media']['prompt_ref'] = 'wu-example-001-reference';
        $question['source']['reference'] = 'user-provided-source/WU-example';
        $question['metadata']['internal'] = [
            'draft_logical_id' => 'wu-example-001',
            'source_item_number' => 'example',
            'rationale' => 'Contoh sumber menyatakan target merupakan kubus A dalam kedudukan berbeda.',
            'matching_masters' => ['A'],
            'proper_rotations_checked' => 24,
            'reflection_used' => false,
        ];

        foreach ($question['options'] as &$option) {
            $key = strtoupper((string) $option['key']);
            $option['text'] = null;
            $option['correct'] = $key === 'A';
            $option['score'] = $key === 'A' ? 1 : 0;
        }
        unset($option);

        continue;
    }

    $number = (int) ($question['display_order'] ?? 0);

    if (($question['kind'] ?? null) !== 'scored' || ! isset($plan[$number])) {
        throw new RuntimeException('Record scored WU tidak sesuai plan sumber 137–148.');
    }

    $item = $plan[$number];
    $foundScored[$number] = true;
    $question['prompt'] = 'Perhatikan satu kubus target. Pilih kubus acuan A–E yang identik setelah rotasi legal.';
    $question['explanation'] = null;
    $question['difficulty_target'] = $item['difficulty'];
    $question['media']['prompt_ref'] = 'wu-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'-reference';
    $question['source']['reference'] = 'user-provided-source/WU-'.$item['source'];
    $question['metadata']['internal'] = [
        'draft_logical_id' => 'wu-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
        'source_item_number' => $item['source'],
        'rationale' => "Target sumber {$item['source']} hanya cocok dengan master {$item['key']} melalui proper cube rotation.",
        'matching_masters' => [$item['key']],
        'proper_rotations_checked' => 24,
        'reflection_used' => false,
    ];

    foreach ($question['options'] as &$option) {
        $key = strtoupper((string) $option['key']);
        $option['text'] = null;
        $option['correct'] = $key === $item['key'];
        $option['score'] = $key === $item['key'] ? 1 : 0;
    }
    unset($option);
}
unset($question);

if (! $foundExample || array_keys($foundScored) !== range(1, 12)) {
    throw new RuntimeException('Canonical WU harus mempunyai 1 example dan 12 scored records.');
}

$difficulty = ['easy' => 0, 'medium' => 0, 'hard' => 0];
$keys = [];

foreach ($wu['questions'] as $question) {
    $options = $question['options'] ?? [];
    $correct = array_values(array_filter(
        $options,
        static fn (array $option): bool => ($option['correct'] ?? false) === true && ($option['score'] ?? null) === 1,
    ));

    if (count($options) !== 5
        || array_column($options, 'key') !== ['A', 'B', 'C', 'D', 'E']
        || count($correct) !== 1) {
        throw new RuntimeException("Kontrak opsi tidak valid: {$question['logical_id']}");
    }

    if ($question['kind'] === 'scored') {
        $difficulty[$question['difficulty_target']]++;
        $keys[$question['display_order']] = $correct[0]['key'];
    }
}

if ($difficulty !== ['easy' => 4, 'medium' => 5, 'hard' => 3]
    || $keys !== array_map(static fn (array $item): string => $item['key'], $plan)) {
    throw new RuntimeException('Difficulty atau answer key canonical WU tidak sesuai plan final.');
}

wuWriteJson($wuPath, $wu);

$metadata = wuReadJson($metadataPath);
$wuMediaCount = 0;

foreach ($metadata['media'] as &$record) {
    if (($record['subtest_code'] ?? null) !== 'WU') {
        continue;
    }

    $relativePath = (string) ($record['relative_path'] ?? '');
    $absolutePath = $datasetDir.'/'.$relativePath;

    if (! str_starts_with($relativePath, 'media/wu/') || ! is_file($absolutePath)) {
        throw new RuntimeException("Media metadata WU tidak valid: {$relativePath}");
    }

    $record['byte_size'] = filesize($absolutePath);
    $record['sha256'] = hash_file('sha256', $absolutePath);
    $record['alt_text'] = ($record['role'] ?? null) === 'option'
        ? 'Kubus acuan '.($record['option_code'] ?? '')
        : 'Satu kubus target WU';
    $record['provenance'] = 'user-reviewed-source-trace-137-148';
    $record['review_status'] = 'human_review_passed';
    $record['active'] = false;
    $wuMediaCount++;
}
unset($record);

if ($wuMediaCount !== 78) {
    throw new RuntimeException("Jumlah media WU harus 78, ditemukan {$wuMediaCount}.");
}

wuWriteJson($metadataPath, $metadata);

$manifest = wuReadJson($manifestPath);
$foundManifestWu = false;

foreach ($manifest['subtests'] as &$subtest) {
    if (($subtest['code'] ?? null) === 'WU') {
        $subtest['max_score'] = 23;
        $foundManifestWu = true;
    }
}
unset($subtest);

if (! $foundManifestWu) {
    throw new RuntimeException('Entry WU tidak ditemukan pada manifest.');
}

$subtestHashes = [];

foreach ($manifest['subtests'] as $subtest) {
    $file = (string) $subtest['file'];
    $subtestHashes[$file] = hash_file('sha256', $datasetDir.'/'.$file);
}
ksort($subtestHashes, SORT_STRING);

$manifest['content_fingerprint'] = hash('sha256', implode("\n", array_map(
    static fn (string $path, string $hash): string => "{$path}:{$hash}",
    array_keys($subtestHashes),
    array_values($subtestHashes),
)));

$mediaParts = array_map(
    static fn (array $record): string => $record['logical_id'].':'.$record['sha256'],
    $metadata['media'],
);
sort($mediaParts, SORT_STRING);
$manifest['media_checksum_aggregate'] = hash('sha256', implode("\n", $mediaParts));
$manifest['media']['count'] = count($metadata['media']);
wuWriteJson($manifestPath, $manifest);

$checksums = wuReadJson($checksumsPath);
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $datasetDir,
    RecursiveDirectoryIterator::SKIP_DOTS,
));

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($datasetDir) + 1));

    if ($relative !== 'checksums.json') {
        $files[$relative] = hash_file('sha256', $file->getPathname());
    }
}
ksort($files, SORT_STRING);
$checksums['algorithm'] = 'sha256';
$checksums['files'] = $files;
wuWriteJson($checksumsPath, $checksums);

echo "WU SOURCE 137-148 GENERATOR: PASS\n";
echo 'EXAMPLE_KEY=A'.PHP_EOL;
echo 'SCORED_KEYS='.implode(',', $keys).PHP_EOL;
echo 'DIFFICULTY=4/5/3'.PHP_EOL;
echo 'WEIGHTED_MAXIMUM=23'.PHP_EOL;
echo 'MEDIA_WU=78'.PHP_EOL;

