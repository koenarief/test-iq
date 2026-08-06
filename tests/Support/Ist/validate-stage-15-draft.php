<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 3);
$draftRoot = $projectRoot.'/docs/ist/content-drafts/stage-15-assets';
$specPath = $draftRoot.'/geometry-spec-draft.json';
$manifestPath = $draftRoot.'/media-manifest-draft.json';
$errors = [];

$fail = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

$readJson = static function (string $path) use ($fail): array {
    $contents = @file_get_contents($path);

    if (! is_string($contents)) {
        $fail("File tidak dapat dibaca: {$path}");

        return [];
    }

    try {
        $value = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $fail("JSON tidak valid pada {$path}: {$exception->getMessage()}");

        return [];
    }

    if (! is_array($value)) {
        $fail("Root JSON harus object: {$path}");

        return [];
    }

    return $value;
};

$specRaw = @file_get_contents($specPath);
$manifestRaw = @file_get_contents($manifestPath);
$spec = $readJson($specPath);
$manifest = $readJson($manifestPath);

foreach ([$specRaw, $manifestRaw] as $index => $raw) {
    if (! is_string($raw)) {
        continue;
    }

    if (preg_match('/\bDEV\b/u', $raw) === 1) {
        $fail('Marker DEV ditemukan pada payload draft #'.($index + 1));
    }

    if (preg_match('/Soal IST\.pdf|IST_Hasil_dan_Norma\.xlsx|workbook/iu', $raw) === 1) {
        $fail('Referensi instrumen sumber ditemukan pada payload draft #'.($index + 1));
    }
}

$expectedStatus = [
    'overall_status' => 'human_review_passed',
    'active' => false,
    'approved' => false,
    'frozen' => false,
    'imported' => false,
];

foreach ($expectedStatus as $field => $expected) {
    if (($spec['status'][$field] ?? null) !== $expected) {
        $fail("Status spec {$field} tidak sesuai");
    }

    if (($manifest[$field] ?? null) !== $expected) {
        $fail("Status manifest {$field} tidak sesuai");
    }
}

foreach (['human_visual_review', 'human_geometry_review', 'human_rotation_review', 'human_language_review', 'human_logic_review'] as $field) {
    if (($spec['status'][$field] ?? null) !== 'passed') {
        $fail("{$field} harus passed");
    }
}

$records = $spec['records'] ?? null;

if (! is_array($records)) {
    $fail('Records spec harus array');
    $records = [];
}

$expectedIds = ['fa-example-001'];
for ($number = 1; $number <= 10; $number++) {
    $expectedIds[] = sprintf('fa-%03d', $number);
}
$expectedIds[] = 'wu-example-001';
for ($number = 1; $number <= 12; $number++) {
    $expectedIds[] = sprintf('wu-%03d', $number);
}

$recordIds = array_map(static fn (array $record): mixed => $record['logical_id'] ?? null, $records);

if ($recordIds !== $expectedIds) {
    $fail('Logical ID atau urutan 24 record tidak sesuai');
}

$difficulty = [
    'FA' => ['easy' => 0, 'medium' => 0, 'hard' => 0],
    'WU' => ['easy' => 0, 'medium' => 0, 'hard' => 0],
];
$keyCounts = [
    'FA' => array_fill_keys(['A', 'B', 'C', 'D', 'E'], 0),
    'WU' => array_fill_keys(['A', 'B', 'C', 'D', 'E'], 0),
];
$subtestCounts = ['FA' => 0, 'WU' => 0];
$exampleCount = 0;
$scoredCount = 0;
$expectedMedia = [];

$cellKey = static fn (array $cell): string => ((int) ($cell[0] ?? 0)).','.((int) ($cell[1] ?? 0));

$normalizeCells = static function (array $cells) use ($cellKey): array {
    $minimumX = min(array_map(static fn (array $cell): int => (int) $cell[0], $cells));
    $minimumY = min(array_map(static fn (array $cell): int => (int) $cell[1], $cells));
    $normalized = array_map(
        static fn (array $cell): array => [(int) $cell[0] - $minimumX, (int) $cell[1] - $minimumY],
        $cells,
    );
    usort($normalized, static fn (array $left, array $right): int => [$left[1], $left[0]] <=> [$right[1], $right[0]]);

    return $normalized;
};

$cellSetKey = static function (array $cells) use ($normalizeCells, $cellKey): string {
    return implode(';', array_map($cellKey, $normalizeCells($cells)));
};

$orientations = static function (array $piece, bool $allowReflection = false) use ($normalizeCells, $cellSetKey): array {
    $orientations = [];

    foreach ($allowReflection ? [false, true] : [false] as $reflect) {
        for ($rotation = 0; $rotation < 4; $rotation++) {
            $transformed = [];

            foreach ($piece as $cell) {
                $x = (int) $cell[0];
                $y = (int) $cell[1];

                if ($reflect) {
                    $x = -$x;
                }

                for ($step = 0; $step < $rotation; $step++) {
                    [$x, $y] = [-$y, $x];
                }

                $transformed[] = [$x, $y];
            }

            $normalized = $normalizeCells($transformed);
            $orientations[$cellSetKey($normalized)] = $normalized;
        }
    }

    return array_values($orientations);
};

$canTile = static function (array $target, array $pieces, bool $allowReflection = false) use ($orientations, $cellKey): bool {
    $expectedArea = array_sum(array_map('count', $pieces));

    if (count($target) !== $expectedArea) {
        return false;
    }

    $targetSet = [];
    foreach ($target as $cell) {
        $targetSet[$cellKey($cell)] = [(int) $cell[0], (int) $cell[1]];
    }

    if (count($targetSet) !== count($target)) {
        return false;
    }

    $pieceOrientations = array_map(
        static fn (array $piece): array => $orientations($piece, $allowReflection),
        $pieces,
    );
    $memo = [];

    $search = static function (array $remaining, int $usedMask) use (&$search, &$memo, $pieceOrientations, $cellKey, $pieces): bool {
        if ($remaining === []) {
            return $usedMask === (1 << count($pieces)) - 1;
        }

        ksort($remaining);
        $memoKey = $usedMask.'|'.implode(';', array_keys($remaining));

        if (array_key_exists($memoKey, $memo)) {
            return $memo[$memoKey];
        }

        $anchor = reset($remaining);

        foreach ($pieceOrientations as $pieceIndex => $variants) {
            if (($usedMask & (1 << $pieceIndex)) !== 0) {
                continue;
            }

            foreach ($variants as $variant) {
                foreach ($variant as $origin) {
                    $offsetX = $anchor[0] - $origin[0];
                    $offsetY = $anchor[1] - $origin[1];
                    $placed = [];
                    $fits = true;

                    foreach ($variant as $cell) {
                        $key = $cellKey([$cell[0] + $offsetX, $cell[1] + $offsetY]);

                        if (! isset($remaining[$key])) {
                            $fits = false;
                            break;
                        }

                        $placed[] = $key;
                    }

                    if (! $fits) {
                        continue;
                    }

                    $next = $remaining;
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

    return $search($targetSet, 0);
};

$faceVectors = [
    'top' => [0, 0, 1],
    'bottom' => [0, 0, -1],
    'front' => [0, -1, 0],
    'back' => [0, 1, 0],
    'right' => [1, 0, 0],
    'left' => [-1, 0, 0],
];
$cross = static fn (array $left, array $right): array => [
    $left[1] * $right[2] - $left[2] * $right[1],
    $left[2] * $right[0] - $left[0] * $right[2],
    $left[0] * $right[1] - $left[1] * $right[0],
];
$validCubeTuple = static function (array $tuple) use ($faceVectors, $cross): bool {
    if (count($tuple) !== 3 || count(array_unique($tuple)) !== 3) {
        return false;
    }

    foreach ($tuple as $face) {
        if (! isset($faceVectors[$face])) {
            return false;
        }
    }

    return $cross($faceVectors[$tuple[0]], $faceVectors[$tuple[1]]) === $faceVectors[$tuple[2]];
};

$rotationCount = 0;
foreach (array_keys($faceVectors) as $top) {
    foreach (array_keys($faceVectors) as $front) {
        foreach (array_keys($faceVectors) as $right) {
            if ($validCubeTuple([$top, $front, $right])) {
                $rotationCount++;
            }
        }
    }
}
if ($rotationCount !== 24) {
    $fail("Enumerasi rotasi kubus menghasilkan {$rotationCount}, bukan 24");
}

foreach ($records as $recordIndex => $record) {
    if (! is_array($record)) {
        $fail("Record #{$recordIndex} bukan object");
        continue;
    }

    $id = $record['logical_id'] ?? "record-{$recordIndex}";
    $subtest = $record['subtest_code'] ?? null;
    $kind = $record['kind'] ?? null;

    if (! isset($subtestCounts[$subtest])) {
        $fail("{$id}: subtest tidak valid");
        continue;
    }

    $subtestCounts[$subtest]++;

    if ($kind === 'example') {
        $exampleCount++;
        if (($record['display_order'] ?? null) !== 0
            || ! array_key_exists('difficulty_target', $record)
            || $record['difficulty_target'] !== null) {
            $fail("{$id}: kontrak example tidak valid");
        }
    } elseif ($kind === 'scored') {
        $scoredCount++;
        $level = $record['difficulty_target'] ?? null;
        if (! isset($difficulty[$subtest][$level])) {
            $fail("{$id}: difficulty tidak valid");
        } else {
            $difficulty[$subtest][$level]++;
        }
    } else {
        $fail("{$id}: kind tidak valid");
    }

    if (($record['answer_type'] ?? null) !== 'image_choice'
        || ($record['content_origin'] ?? null) !== 'original_internal'
        || ! array_key_exists('source_reference', $record)
        || $record['source_reference'] !== null
        || ($record['copyright_status'] ?? null) !== 'internally_authored'
        || ($record['normative_compatibility'] ?? null) !== 'none'
        || ($record['review_status'] ?? null) !== 'human_review_passed'
        || ($record['active'] ?? null) !== false) {
        $fail("{$id}: metadata record tidak valid");
    }

    foreach (['automated_visual_review', 'automated_geometry_review', 'automated_language_review', 'automated_logic_review', 'qc_status'] as $review) {
        if (($record['reviews'][$review] ?? null) !== 'pass') {
            $fail("{$id}: {$review} tidak pass");
        }
    }

    if ($subtest === 'WU' && ($record['reviews']['automated_rotation_review'] ?? null) !== 'pass') {
        $fail("{$id}: automated_rotation_review tidak pass");
    }

    $options = $record['options'] ?? null;
    if (! is_array($options) || count($options) !== 5) {
        $fail("{$id}: harus mempunyai lima opsi");
        continue;
    }

    if (array_map(static fn (array $option): mixed => $option['code'] ?? null, $options) !== ['A', 'B', 'C', 'D', 'E']) {
        $fail("{$id}: urutan kode opsi tidak valid");
    }

    $correct = array_values(array_filter($options, static fn (array $option): bool => ($option['is_correct'] ?? false) === true));
    if (count($correct) !== 1) {
        $fail("{$id}: jumlah opsi benar bukan satu");
    } else {
        if (($correct[0]['score_value'] ?? null) !== 1) {
            $fail("{$id}: skor opsi benar bukan 1");
        }

        if ($kind === 'scored') {
            $keyCounts[$subtest][$correct[0]['code']]++;
        }
    }

    foreach ($options as $option) {
        $expectedScore = ($option['is_correct'] ?? false) === true ? 1 : 0;
        if (($option['score_value'] ?? null) !== $expectedScore
            || ! is_string($option['rationale_internal'] ?? null)
            || trim($option['rationale_internal']) === '') {
            $fail("{$id} opsi ".($option['code'] ?? '?').': skor atau rationale tidak valid');
        }
    }

    $promptId = $record['prompt_media_id'] ?? null;
    if (! is_string($promptId) || $promptId === '') {
        $fail("{$id}: prompt media ID tidak valid");
    } else {
        $expectedMedia[$promptId] = [
            'question' => $id,
            'option' => null,
            'path' => $record['prompt_relative_path'] ?? null,
            'role' => $subtest === 'FA' ? 'prompt' : 'reference',
        ];
    }

    foreach ($options as $option) {
        $mediaId = $option['media_id'] ?? null;
        $prefix = strtolower($subtest);
        $number = sprintf('%03d', (int) ($record['display_order'] ?? 0));
        $path = $kind === 'example'
            ? "{$prefix}/examples/{$prefix}-example-001-option-".strtolower($option['code']).'.svg'
            : "{$prefix}/options/{$prefix}-q{$number}-option-".strtolower($option['code']).'.svg';
        $expectedMedia[$mediaId] = ['question' => $id, 'option' => $option['code'], 'path' => $path, 'role' => 'option'];
    }

    if ($subtest === 'FA') {
        $pieces = $record['pieces'] ?? [];
        $placements = $record['correct_placements'] ?? [];
        $target = $record['visual_logic']['correct_target_cells'] ?? [];

        if (count($pieces) !== 3 || count($placements) !== 3
            || array_sum(array_map('count', $pieces)) !== count($target)) {
            $fail("{$id}: definisi potongan atau area FA tidak valid");
        }

        $placementUnion = [];
        foreach ($placements as $pieceIndex => $placement) {
            $placementKey = $cellSetKey($placement);
            $validPlacement = array_any(
                $orientations($pieces[$pieceIndex] ?? []),
                static fn (array $variant): bool => $cellSetKey($variant) === $placementKey,
            );
            if (! $validPlacement) {
                $fail("{$id}: placement potongan #{$pieceIndex} bukan rotasi legal");
            }

            foreach ($placement as $cell) {
                $key = $cellKey($cell);
                if (isset($placementUnion[$key])) {
                    $fail("{$id}: placement correct overlap");
                }
                $placementUnion[$key] = true;
            }
        }

        if ($cellSetKey(array_map(static fn (string $key): array => array_map('intval', explode(',', $key)), array_keys($placementUnion))) !== $cellSetKey($target)) {
            $fail("{$id}: union placement tidak sama dengan target correct");
        }

        foreach ($options as $option) {
            $tileable = $canTile($option['cells'] ?? [], $pieces, false);
            if ($tileable !== (($option['is_correct'] ?? false) === true)) {
                $fail("{$id} opsi {$option['code']}: hasil exact-cover tidak cocok dengan key");
            }

            if (($option['failure_mode'] ?? null) === 'reflection_required') {
                if ($tileable || ! $canTile($option['cells'] ?? [], $pieces, true)) {
                    $fail("{$id} opsi {$option['code']}: klaim reflection_required tidak terbukti");
                }
            }
        }

        foreach (['area_composition', 'topology', 'rotation', 'reflection', 'small_screen', 'ambiguity'] as $audit) {
            if (($record['audits'][$audit] ?? null) !== 'pass') {
                $fail("{$id}: audit FA {$audit} tidak pass");
            }
        }
    } else {
        $mapping = $record['face_symbols'] ?? [];
        if (array_keys($mapping) !== ['top', 'bottom', 'front', 'back', 'right', 'left']
            || count(array_unique(array_values($mapping))) !== 6) {
            $fail("{$id}: mapping enam simbol WU tidak valid");
        }

        $referenceViews = $record['reference_views'] ?? [];
        if (count($referenceViews) !== 2
            || ! $validCubeTuple($referenceViews[0] ?? [])
            || ! $validCubeTuple($referenceViews[1] ?? [])
            || count(array_unique(array_merge(...$referenceViews))) !== 6) {
            $fail("{$id}: dua reference views tidak valid atau tidak mencakup enam sisi");
        }

        foreach ($options as $option) {
            $valid = $validCubeTuple($option['visible_faces'] ?? []);
            if ($valid !== (($option['is_correct'] ?? false) === true)) {
                $fail("{$id} opsi {$option['code']}: validitas rotasi tidak cocok dengan key");
            }
        }

        if (! is_array($record['rotation_proof']['rotation_sequence'] ?? null)
            || ! is_string($record['rotation_proof']['statement'] ?? null)
            || trim($record['rotation_proof']['statement']) === '') {
            $fail("{$id}: rotation proof tidak lengkap");
        }

        foreach (['adjacency', 'opposite_faces', 'chirality', 'symbol_orientation', 'small_screen', 'ambiguity'] as $audit) {
            if (($record['audits'][$audit] ?? null) !== 'pass') {
                $fail("{$id}: audit WU {$audit} tidak pass");
            }
        }
    }
}

if (count($records) !== 24 || $exampleCount !== 2 || $scoredCount !== 22
    || $subtestCounts !== ['FA' => 11, 'WU' => 13]) {
    $fail('Jumlah record/example/scored/subtest tidak sesuai');
}

if ($difficulty['FA'] !== ['easy' => 3, 'medium' => 4, 'hard' => 3]
    || $difficulty['WU'] !== ['easy' => 4, 'medium' => 5, 'hard' => 3]) {
    $fail('Distribusi difficulty tidak sesuai');
}

if ($keyCounts['FA'] !== ['A' => 2, 'B' => 2, 'C' => 2, 'D' => 2, 'E' => 2]
    || $keyCounts['WU'] !== ['A' => 2, 'B' => 2, 'C' => 3, 'D' => 3, 'E' => 2]) {
    $fail('Distribusi key tidak sesuai');
}

$media = $manifest['media'] ?? null;
if (! is_array($media) || count($media) !== 144 || ($manifest['media_count'] ?? null) !== 144) {
    $fail('Manifest harus memuat 144 media');
    $media = [];
}

$manifestIds = [];
$manifestPaths = [];
foreach ($media as $index => $item) {
    $id = $item['logical_id'] ?? "media-{$index}";
    $relativePath = $item['relative_path'] ?? null;

    if (isset($manifestIds[$id])) {
        $fail("Media ID duplikat: {$id}");
    }
    if (is_string($relativePath) && isset($manifestPaths[$relativePath])) {
        $fail("Media path duplikat: {$relativePath}");
    }
    $manifestIds[$id] = true;
    if (is_string($relativePath)) {
        $manifestPaths[$relativePath] = true;
    }

    $expected = $expectedMedia[$id] ?? null;
    if ($expected === null
        || $relativePath !== $expected['path']
        || ($item['media_role'] ?? null) !== $expected['role']
        || ($item['question_logical_id'] ?? null) !== $expected['question']
        || ($item['option_code'] ?? null) !== $expected['option']) {
        $fail("Kontrak manifest media tidak cocok: {$id}");
    }

    if (! is_string($relativePath)
        || preg_match('/\A(?:fa|wu)\/(?:examples|questions|options)\/[a-z0-9-]+\.svg\z/', $relativePath) !== 1
        || preg_match('/correct|key|benar|answer|score/i', $relativePath) === 1) {
        $fail("Nama/path media tidak aman: {$id}");
        continue;
    }

    $expectedAlt = ($item['media_role'] ?? null) === 'option'
        ? 'Kubus pilihan '.($item['option_code'] ?? '')
        : (($item['subtest_code'] ?? null) === 'FA' ? 'Diagram potongan FA' : 'Diagram referensi WU');
    if (($item['subtest_code'] ?? null) === 'FA' && ($item['media_role'] ?? null) === 'option') {
        $expectedAlt = 'Diagram pilihan '.($item['option_code'] ?? '');
    }

    if (($item['alt_text'] ?? null) !== $expectedAlt
        || preg_match('/correct|key|benar|jawaban|score/i', (string) ($item['alt_text'] ?? '')) === 1) {
        $fail("Alt text media tidak netral: {$id}");
    }

    if (($item['mime_type'] ?? null) !== 'image/svg+xml'
        || ($item['content_origin'] ?? null) !== 'original_internal'
        || ($item['copyright_status'] ?? null) !== 'internally_authored'
        || ($item['review_status'] ?? null) !== 'human_review_passed'
        || ($item['active'] ?? null) !== false) {
        $fail("Metadata manifest tidak valid: {$id}");
    }

    $source = $draftRoot.'/'.$relativePath;
    if (! is_file($source) || is_link($source)) {
        $fail("Media hilang atau symlink: {$id}");
        continue;
    }

    if (($item['byte_size'] ?? null) !== filesize($source)
        || ! is_string($item['sha256'] ?? null)
        || ! hash_equals((string) $item['sha256'], hash_file('sha256', $source))) {
        $fail("Byte size atau checksum tidak cocok: {$id}");
    }

    $svg = file_get_contents($source);
    if (! is_string($svg)
        || preg_match('/<script|<foreignObject|\son[a-z]+\s*=|javascript:|<image|<iframe|<object|<embed/iu', $svg) === 1
        || preg_match('/(?:href|src)\s*=\s*["\'](?!#)/iu', $svg) === 1
        || preg_match('/@import|url\s*\(/iu', $svg) === 1) {
        $fail("SVG memuat elemen/referensi berbahaya: {$id}");
        continue;
    }

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $document->documentElement;

    if (! $loaded || ! $root instanceof DOMElement || $root->localName !== 'svg') {
        $fail("SVG XML tidak valid: {$id}");
        continue;
    }

    $width = (int) $root->getAttribute('width');
    $height = (int) $root->getAttribute('height');
    $viewBox = trim($root->getAttribute('viewBox'));
    if (($item['width'] ?? null) !== $width
        || ($item['height'] ?? null) !== $height
        || ($item['view_box'] ?? null) !== $viewBox
        || $viewBox !== "0 0 {$width} {$height}"
        || $width < 1 || $height < 1) {
        $fail("Dimensi/viewBox tidak cocok: {$id}");
    }
}

if (count($expectedMedia) !== 144
    || array_keys($manifestIds) !== array_keys($expectedMedia)) {
    $fail('Daftar media manifest tidak sama dengan referensi 24 record');
}

$actualSvgPaths = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($draftRoot, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'svg') {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($draftRoot) + 1));
        $actualSvgPaths[$relative] = true;
    }
}
ksort($actualSvgPaths);
$declaredPaths = $manifestPaths;
ksort($declaredPaths);
if (array_keys($actualSvgPaths) !== array_keys($declaredPaths)) {
    $fail('Daftar file SVG aktual berbeda dari manifest');
}

foreach (['fa.json', 'wu.json'] as $productionFile) {
    if (file_exists($projectRoot.'/database/data/ist-final/'.$productionFile)) {
        $fail("Dataset produksi tidak boleh ada pada Tahap 15: {$productionFile}");
    }
}

$contactSheet = @file_get_contents($draftRoot.'/contact-sheet.html');
if (! is_string($contactSheet)
    || ! str_contains($contactSheet, 'INTERNAL REVIEW — NOT ACTIVE')
    || preg_match_all('/<img\s+src="([^"]+)"/u', $contactSheet, $contactMatches) !== 144) {
    $fail('Contact sheet tidak lengkap atau watermark tidak tersedia');
} else {
    $contactPaths = array_fill_keys($contactMatches[1], true);
    ksort($contactPaths);
    if (array_keys($contactPaths) !== array_keys($declaredPaths)) {
        $fail('Daftar media contact sheet berbeda dari manifest');
    }
}

printf("records=%d; examples=%d; scored=%d\n", count($records), $exampleCount, $scoredCount);
printf("FA records=%d; difficulty=%s; keys=%s\n", $subtestCounts['FA'], json_encode($difficulty['FA']), json_encode($keyCounts['FA']));
printf("WU records=%d; difficulty=%s; keys=%s; proper_rotations=%d\n", $subtestCounts['WU'], json_encode($difficulty['WU']), json_encode($keyCounts['WU']), $rotationCount);
printf("media=%d; svg_files=%d; status=human_review_passed; active=false\n", count($media), count($actualSvgPaths));

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "ERROR: {$error}\n");
    }

    exit(1);
}

echo "STAGE 15 VALIDATION PASSED\n";
