<?php

declare(strict_types=1);

/**
 * Restore all human-reviewed FA media from Stage 15. Prompt/piece SVG geometry
 * is copied exactly (with only EOF whitespace normalized), while per-item
 * options retain their reviewed geometry and receive the approved rendering.
 */

$root = dirname(__DIR__);
$baselineRoot = $root.'/docs/ist/content-drafts/stage-15-assets/fa';
$baselineManifestPath = $root.'/docs/ist/content-drafts/stage-15-assets/media-manifest-draft.json';
$datasetRoot = $root.'/database/data/ist-final-staging';
$metadataPath = $datasetRoot.'/media/metadata.json';
$manifestPath = $datasetRoot.'/manifest.json';
$checksumsPath = $datasetRoot.'/checksums.json';

function faReadJson(string $path): array
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Tidak dapat membaca {$path}.");
    }

    return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
}

function faWriteJson(string $path, array $payload): void
{
    $contents = json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    )."\n";

    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Tidak dapat menulis {$path}.");
    }
}

/** @return list<array{x:int,y:int,width:int,height:int}> */
function faRectangles(string $svg, string $path): array
{
    preg_match_all('/<rect\s+([^>]+)\/>/', $svg, $matches);
    $rectangles = [];

    foreach ($matches[1] as $attributes) {
        $values = [];

        foreach (['x', 'y', 'width', 'height'] as $name) {
            if (preg_match('/(?:^|\s)'.preg_quote($name, '/').'="(-?[0-9]+)"/', $attributes, $match) !== 1) {
                throw new RuntimeException("Atribut {$name} tidak tersedia pada {$path}.");
            }

            $values[$name] = (int) $match[1];
        }

        $rectangles[] = $values;
    }

    if ($rectangles === []) {
        throw new RuntimeException("Sel baseline tidak ditemukan pada {$path}.");
    }

    return $rectangles;
}

function faGeometryFingerprint(array $rectangles): string
{
    $coordinates = array_map(
        static fn (array $rect): string => implode(',', [
            $rect['x'],
            $rect['y'],
            $rect['width'],
            $rect['height'],
        ]),
        $rectangles,
    );
    sort($coordinates, SORT_STRING);

    return hash('sha256', implode(';', $coordinates));
}

/** @return array{width:int,height:int,view_box:string} */
function faSvgDimensions(string $svg, string $path): array
{
    if (preg_match('/<svg\b([^>]*)>/', $svg, $rootMatch) !== 1) {
        throw new RuntimeException("Root SVG tidak valid pada {$path}.");
    }

    $attributes = $rootMatch[1];
    $values = [];

    foreach (['width', 'height', 'viewBox'] as $name) {
        if (preg_match('/(?:^|\s)'.preg_quote($name, '/').'="([^"]+)"/', $attributes, $match) !== 1) {
            throw new RuntimeException("Atribut {$name} tidak tersedia pada {$path}.");
        }

        $values[$name] = $match[1];
    }

    return [
        'width' => (int) $values['width'],
        'height' => (int) $values['height'],
        'view_box' => $values['viewBox'],
    ];
}

function faStyledOption(string $svg, string $path): string
{
    $rectangles = faRectangles($svg, $path);
    $cellSet = [];

    foreach ($rectangles as $rect) {
        $cellSet[$rect['x'].','.$rect['y']] = true;
    }

    $renderedCells = array_map(
        static fn (array $rect): string => sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="#e5e7eb" stroke="#374151" stroke-width="2" vector-effect="non-scaling-stroke" shape-rendering="crispEdges"/>',
            $rect['x'],
            $rect['y'],
            $rect['width'],
            $rect['height'],
        ),
        $rectangles,
    );

    $edges = [];

    foreach ($rectangles as $rect) {
        $left = $rect['x'];
        $top = $rect['y'];
        $right = $left + $rect['width'];
        $bottom = $top + $rect['height'];

        foreach ([
            [-$rect['width'], 0, $left, $top, $left, $bottom],
            [$rect['width'], 0, $right, $top, $right, $bottom],
            [0, -$rect['height'], $left, $top, $right, $top],
            [0, $rect['height'], $left, $bottom, $right, $bottom],
        ] as [$dx, $dy, $x1, $y1, $x2, $y2]) {
            if (! isset($cellSet[($left + $dx).','.($top + $dy)])) {
                $edges[] = sprintf('M%d %dL%d %d', $x1, $y1, $x2, $y2);
            }
        }
    }

    if (preg_match('/\A(<svg\b[^>]*>)/', trim($svg), $rootMatch) !== 1) {
        throw new RuntimeException("Root SVG tidak valid pada {$path}.");
    }

    $rootTag = $rootMatch[1];

    if (! str_contains($rootTag, 'shape-rendering=')) {
        $rootTag = preg_replace('/<svg\b/', '<svg shape-rendering="crispEdges"', $rootTag, 1);
    }

    $styled = $rootTag."\n"
        .implode("\n", $renderedCells)."\n"
        .'<path data-fa-layer="outer-outline" d="'.implode('', $edges).'" fill="none" stroke="#111827" stroke-width="3" vector-effect="non-scaling-stroke" shape-rendering="crispEdges" stroke-linecap="square" stroke-linejoin="miter"/>'
        ."\n</svg>\n";

    if (faGeometryFingerprint($rectangles) !== faGeometryFingerprint(faRectangles($styled, $path))) {
        throw new RuntimeException("Geometri berubah saat styling {$path}.");
    }

    return $styled;
}

$baselineManifest = faReadJson($baselineManifestPath);
$baselineMedia = [];

foreach ($baselineManifest['media'] ?? [] as $record) {
    if (isset($record['relative_path'])) {
        $baselineMedia[$record['relative_path']] = $record;
    }
}

$promptFiles = array_merge(
    glob($baselineRoot.'/examples/*-prompt.svg') ?: [],
    glob($baselineRoot.'/questions/*-prompt.svg') ?: [],
);
sort($promptFiles, SORT_STRING);

if (count($promptFiles) !== 11) {
    throw new RuntimeException('Baseline FA harus mempunyai tepat 11 SVG prompt.');
}

$promptFingerprints = [];

foreach ($promptFiles as $baselinePath) {
    $relativePath = substr($baselinePath, strlen($baselineRoot) + 1);
    $targetPath = $datasetRoot.'/media/fa/'.$relativePath;
    $baseline = file_get_contents($baselinePath);
    $manifestRecord = $baselineMedia['fa/'.$relativePath] ?? null;

    if ($baseline === false) {
        throw new RuntimeException("Tidak dapat membaca {$baselinePath}.");
    }

    if (! is_array($manifestRecord)
        || ($manifestRecord['review_status'] ?? null) !== 'human_review_passed'
        || ! hash_equals((string) ($manifestRecord['sha256'] ?? ''), hash('sha256', $baseline))) {
        throw new RuntimeException("Prompt baseline tidak cocok dengan manifest human-reviewed: {$relativePath}.");
    }

    $fingerprint = faGeometryFingerprint(faRectangles($baseline, $relativePath));
    $restored = rtrim($baseline)."\n";

    if (file_put_contents($targetPath, $restored) === false) {
        throw new RuntimeException("Tidak dapat menulis {$targetPath}.");
    }

    if ($fingerprint !== faGeometryFingerprint(faRectangles((string) file_get_contents($targetPath), $targetPath))) {
        throw new RuntimeException("Geometri prompt berubah saat restore {$relativePath}.");
    }

    $promptFingerprints[$relativePath] = $fingerprint;
}

$optionFiles = array_merge(
    glob($baselineRoot.'/examples/*-option-*.svg') ?: [],
    glob($baselineRoot.'/options/*.svg') ?: [],
);
sort($optionFiles, SORT_STRING);

if (count($optionFiles) !== 55) {
    throw new RuntimeException('Baseline FA harus mempunyai tepat 55 SVG opsi.');
}

foreach ($optionFiles as $baselinePath) {
    $relativePath = substr($baselinePath, strlen($baselineRoot) + 1);
    $targetPath = $datasetRoot.'/media/fa/'.$relativePath;
    $baseline = file_get_contents($baselinePath);

    if ($baseline === false) {
        throw new RuntimeException("Tidak dapat membaca {$baselinePath}.");
    }

    $styled = faStyledOption($baseline, $relativePath);

    if (file_put_contents($targetPath, $styled) === false) {
        throw new RuntimeException("Tidak dapat menulis {$targetPath}.");
    }
}

$metadata = faReadJson($metadataPath);
$updatedOptions = 0;
$updatedPrompts = 0;

foreach ($metadata['media'] as &$record) {
    if (($record['subtest_code'] ?? null) !== 'FA') {
        continue;
    }

    $path = $datasetRoot.'/'.$record['relative_path'];

    if (($record['role'] ?? null) === 'prompt') {
        $svg = file_get_contents($path);

        if ($svg === false) {
            throw new RuntimeException("Tidak dapat membaca {$path}.");
        }

        $dimensions = faSvgDimensions($svg, $path);
        $record['byte_size'] = filesize($path);
        $record['width'] = $dimensions['width'];
        $record['height'] = $dimensions['height'];
        $record['view_box'] = $dimensions['view_box'];
        $record['sha256'] = hash_file('sha256', $path);
        $record['provenance'] = 'stage-15-human-reviewed-prompt-baseline';
        unset($record['duplicate_hash_reason']);
        $updatedPrompts++;

        continue;
    }

    if (($record['role'] ?? null) !== 'option') {
        continue;
    }

    $record['byte_size'] = filesize($path);
    $record['sha256'] = hash_file('sha256', $path);
    $record['provenance'] = 'stage-15-human-reviewed-geometry-styling-only';
    unset($record['duplicate_hash_reason']);
    $updatedOptions++;
}
unset($record);

if ($updatedOptions !== 55) {
    throw new RuntimeException("Metadata opsi FA harus tepat 55, ditemukan {$updatedOptions}.");
}

if ($updatedPrompts !== 11) {
    throw new RuntimeException("Metadata prompt FA harus tepat 11, ditemukan {$updatedPrompts}.");
}

faWriteJson($metadataPath, $metadata);

$manifest = faReadJson($manifestPath);
$aggregateParts = array_map(
    static fn (array $record): string => $record['logical_id'].':'.$record['sha256'],
    $metadata['media'],
);
sort($aggregateParts, SORT_STRING);
$manifest['media_checksum_aggregate'] = hash('sha256', implode("\n", $aggregateParts));
faWriteJson($manifestPath, $manifest);

$checksums = faReadJson($checksumsPath);
$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($datasetRoot, RecursiveDirectoryIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($datasetRoot) + 1));

    if ($relativePath !== 'checksums.json') {
        $files[$relativePath] = hash_file('sha256', $file->getPathname());
    }
}

ksort($files, SORT_STRING);
$checksums['algorithm'] = 'sha256';
$checksums['files'] = $files;
faWriteJson($checksumsPath, $checksums);

echo "FA BASELINE MEDIA RESTORE: SUCCESS\n";
echo "Prompts restored from reviewed geometry: 11\n";
echo "Options restored/styled: 55\n";
echo "Geometry changed: 0\n";
