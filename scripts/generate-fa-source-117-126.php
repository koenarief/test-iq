<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$staging = $root.'/database/data/ist-final-staging';
$mediaRoot = $staging.'/media/fa';
$datasetPath = $staging.'/fa.json';
$metadataPath = $staging.'/media/metadata.json';
$manifestPath = $staging.'/manifest.json';
$checksumsPath = $staging.'/checksums.json';

function faSourceReadJson(string $path): array
{
    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException("Tidak dapat membaca {$path}.");
    }

    return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
}

function faSourceWrite(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Tidak dapat menulis {$path}.");
    }
}

function faSourceWriteJson(string $path, array $payload): void
{
    faSourceWrite($path, json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    )."\n");
}

function faSourceSvg(string $body, string $title, string $description, string $viewBox = '0 0 420 250'): string
{
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="420" height="250" viewBox="{$viewBox}" role="img" aria-labelledby="title desc" shape-rendering="geometricPrecision">
  <title id="title">{$title}</title>
  <desc id="desc">{$description}</desc>
  <rect width="100%" height="100%" fill="#ffffff"/>
  <g fill="#111111">{$body}</g>
</svg>
SVG;
}

$masters = [
    'A' => '<path d="M42 166 A168 168 0 0 1 378 166 Z"/>',
    'B' => '<path d="M93 157 C70 111 80 58 121 28 C156 2 219 1 260 26 C297 49 313 92 301 132 C288 176 245 205 190 209 C145 211 108 192 93 157 Z"/>',
    'C' => '<path d="M88 164 C65 132 62 88 76 52 C84 31 97 17 115 7 L180 78 L247 5 C272 33 285 67 285 104 C285 173 234 213 174 214 C138 214 107 195 88 164 Z"/>',
    'D' => '<ellipse cx="210" cy="120" rx="151" ry="88" transform="rotate(-8 210 120)"/>',
    'E' => '<path d="M92 55 L211 174 C188 220 139 239 86 225 C48 215 21 190 10 162 Z" transform="translate(104 -18)"/>',
];

$exampleMasters = [
    'A' => '<circle cx="210" cy="125" r="88"/>',
    'B' => '<path d="M65 177 A145 145 0 0 1 355 177 Z"/>',
    'C' => '<rect x="120" y="35" width="180" height="180"/>',
    'D' => '<path d="M63 190 L334 190 L334 29 Z"/>',
    'E' => '<path d="M210 33 L342 165 C318 209 270 228 210 229 C151 227 103 207 78 165 Z"/>',
];

$prompts = [
    1 => '<path d="M48 54 L48 191 L151 191 C160 177 165 159 165 139 C165 96 139 59 101 54 Z"/><path d="M255 49 C288 57 313 87 313 124 L313 143 C313 183 282 204 239 204 C214 204 192 197 175 184 C211 178 232 164 241 142 C252 116 249 83 255 49 Z"/>',
    2 => '<path d="M51 84 C83 86 105 109 105 139 C105 169 84 190 51 191 Z"/><path d="M145 54 L336 54 C334 82 316 101 287 108 C251 116 204 110 145 110 Z"/><path d="M145 137 C195 137 240 130 279 137 C309 143 329 162 335 190 L145 190 Z"/>',
    3 => '<path d="M45 105 C91 102 128 113 144 153 L143 191 L70 188 Z"/><path d="M276 103 C232 103 196 116 181 155 L184 191 L257 188 Z"/><path d="M184 42 L219 137 L150 137 Z"/>',
    4 => '<path d="M270 53 C319 59 350 93 350 139 C350 177 330 201 296 206 L270 206 Z"/><path d="M78 72 L145 20 L204 79 C184 102 157 112 126 110 C105 107 89 94 78 72 Z"/><path d="M75 150 C89 126 110 114 137 115 C162 116 183 127 199 151 L137 211 Z"/>',
    5 => '<path d="M47 72 C84 73 112 91 126 126 L91 156 C75 169 59 175 47 176 Z"/><path d="M293 65 C258 70 231 89 218 122 L249 153 C266 166 282 173 293 174 Z"/><path d="M169 89 L238 200 C207 186 179 181 149 188 C132 192 117 198 102 208 Z"/>',
    6 => '<path d="M68 94 C107 94 140 98 166 112 C153 139 128 151 96 151 C77 150 64 143 55 132 Z"/><path d="M52 183 L224 183 C219 210 201 221 169 222 L61 222 Z"/><path d="M219 64 C250 41 285 38 324 56 L280 117 Z"/>',
    7 => '<path d="M56 71 L180 36 C179 83 162 119 130 142 L76 154 Z"/><path d="M236 37 L359 76 L337 158 L281 143 C252 119 237 84 236 37 Z"/><path d="M160 176 C190 158 218 158 248 176 L208 218 Z"/>',
    8 => '<path d="M52 78 C83 80 103 103 103 139 C103 171 86 190 52 193 Z"/><path d="M145 139 C146 105 165 87 194 83 L194 193 L145 193 Z"/><rect x="215" y="63" width="48" height="130"/><path d="M284 90 C315 95 335 123 337 193 L284 193 Z"/>',
    9 => '<path d="M50 180 C63 109 101 65 174 39 L119 113 C92 145 69 169 50 180 Z"/><path d="M210 184 C236 128 276 82 336 47 L286 142 C270 174 245 190 210 184 Z"/>',
    10 => '<path d="M45 79 C66 90 77 111 77 141 C76 167 66 187 47 199 L30 183 C45 161 48 133 37 107 Z"/><path d="M87 73 C110 88 121 111 120 139 C119 168 108 190 87 204 L70 187 C86 163 89 132 76 105 Z"/><path d="M193 74 L334 43 L312 116 Z"/><path d="M190 171 L312 137 L334 205 L231 221 Z"/>',
];

$examplePrompt = '<path d="M204 35 A90 90 0 0 0 204 215 L204 183 L181 169 L205 146 L182 123 L205 100 L184 78 L205 62 Z"/><path d="M216 35 A90 90 0 0 1 216 215 L216 183 L239 169 L215 146 L238 123 L215 100 L236 78 L215 62 Z"/>';

$keys = ['A', 'D', 'E', 'C', 'B', 'E', 'D', 'A', 'D', 'C'];
$difficulties = ['easy', 'easy', 'easy', 'medium', 'medium', 'medium', 'medium', 'hard', 'hard', 'hard'];

faSourceWrite($mediaRoot.'/examples/fa-example-001-prompt.svg', faSourceSvg(
    $examplePrompt,
    'Contoh FA sumber',
    'Dua potongan lingkaran dengan sambungan zig-zag.',
));

foreach ($exampleMasters as $key => $body) {
    faSourceWrite(
        $mediaRoot.'/examples/fa-example-001-option-'.strtolower($key).'.svg',
        faSourceSvg($body, "Pilihan contoh FA {$key}", "Bentuk acuan contoh {$key}."),
    );
}

foreach ($prompts as $number => $body) {
    $order = str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    faSourceWrite(
        $mediaRoot."/questions/fa-q{$order}-prompt.svg",
        faSourceSvg($body, "Soal FA {$number}", 'Potongan bentuk untuk disusun menjadi salah satu master A sampai E.'),
    );

    foreach ($masters as $key => $masterBody) {
        faSourceWrite(
            $mediaRoot."/options/fa-q{$order}-option-".strtolower($key).'.svg',
            faSourceSvg($masterBody, "Master FA {$key}", "Bentuk acuan tetap FA {$key}."),
        );
    }
}

$dataset = faSourceReadJson($datasetPath);
$dataset['review_status'] = 'in_review';
$dataset['active'] = false;
$dataset['instruction_content'] = 'Perhatikan seluruh potongan pada gambar. Tentukan bentuk utuh A, B, C, D, atau E yang dapat dibangun menggunakan seluruh potongan tanpa tumpang tindih, tanpa sisa, dan tanpa celah. Potongan boleh diputar, tetapi tidak boleh dicerminkan.';

foreach ($dataset['questions'] as &$question) {
    $isExample = $question['kind'] === 'example';
    $index = $isExample ? null : (int) $question['display_order'] - 1;
    $correctKey = $isExample ? 'A' : $keys[$index];
    $question['prompt'] = $isExample
        ? 'Susun seluruh potongan pada gambar. Bentuk utuh manakah yang dihasilkan?'
        : 'Susun seluruh potongan pada gambar menjadi salah satu bentuk acuan A–E.';
    $question['explanation'] = $isExample
        ? 'Jika kedua potongan disusun dan digabungkan, bentuk yang dihasilkan adalah lingkaran pada pilihan A.'
        : null;
    $question['difficulty_target'] = $isExample ? null : $difficulties[$index];
    $question['review_status'] = 'in_review';
    $question['active'] = false;
    $question['metadata']['internal']['source_number'] = $isExample ? 'example-07' : 117 + $index;
    $question['metadata']['internal']['trace_status'] = 'source_photo_trace_pending_human_review';
    $question['metadata']['internal']['rationale'] = $isExample
        ? 'Dua bagian menutup lingkaran secara utuh.'
        : "Trace sumber nomor ".(117 + $index)." dipetakan ke master {$correctKey}.";

    foreach ($question['options'] as &$option) {
        $option['correct'] = $option['key'] === $correctKey;
        $option['score'] = $option['correct'] ? 1 : 0;
        $option['text'] = null;
    }
    unset($option);
}
unset($question);

faSourceWriteJson($datasetPath, $dataset);

$metadata = faSourceReadJson($metadataPath);
foreach ($metadata['media'] as &$record) {
    if (($record['subtest_code'] ?? null) !== 'FA') {
        continue;
    }

    $path = $staging.'/'.$record['relative_path'];
    $svg = file_get_contents($path);

    if (! is_string($svg) || preg_match('/viewBox="([^"]+)"/', $svg, $match) !== 1) {
        throw new RuntimeException("Media FA tidak valid: {$path}");
    }

    $record['byte_size'] = filesize($path);
    $record['width'] = 420;
    $record['height'] = 250;
    $record['view_box'] = $match[1];
    $record['sha256'] = hash_file('sha256', $path);
    $record['provenance'] = 'source-photo-117-126-trace-pending-human-review';
    $record['review_status'] = 'in_review';
    $record['active'] = false;

    if (str_contains($record['relative_path'], '/options/fa-q')) {
        $record['duplicate_hash_reason'] = 'Lima master FA A-E sengaja digunakan ulang secara identik pada seluruh soal scored.';
    } else {
        unset($record['duplicate_hash_reason']);
    }
}
unset($record);
faSourceWriteJson($metadataPath, $metadata);

$manifest = faSourceReadJson($manifestPath);
foreach ($manifest['subtests'] as &$subtest) {
    if (($subtest['code'] ?? null) === 'FA') {
        $subtest['max_score'] = 20;
    }
}
unset($subtest);

$subtestHashes = [];
foreach (['an', 'fa', 'ge', 'me', 'ra', 'se', 'wa', 'wu', 'zr'] as $code) {
    $file = $code.'.json';
    $subtestHashes[$file] = hash_file('sha256', $staging.'/'.$file);
}
ksort($subtestHashes, SORT_STRING);
$manifest['content_fingerprint'] = hash('sha256', implode("\n", array_map(
    static fn (string $path, string $hash): string => "{$path}:{$hash}",
    array_keys($subtestHashes),
    array_values($subtestHashes),
)));

$mediaParts = [];
foreach ($metadata['media'] as $record) {
    $mediaParts[] = $record['logical_id'].':'.$record['sha256'];
}
sort($mediaParts, SORT_STRING);
$manifest['media_checksum_aggregate'] = hash('sha256', implode("\n", $mediaParts));
faSourceWriteJson($manifestPath, $manifest);

$checksums = faSourceReadJson($checksumsPath);
foreach (array_keys($checksums['files']) as $relative) {
    $path = $staging.'/'.$relative;

    if (is_file($path)) {
        $checksums['files'][$relative] = hash_file('sha256', $path);
    }
}
ksort($checksums['files'], SORT_STRING);
faSourceWriteJson($checksumsPath, $checksums);

echo "FA source trace generated: example=1, scored=10, option-files=55\n";
echo 'Keys='.implode(',', $keys)."\n";
echo "Difficulty=3/4/3; weighted-maximum=20\n";
echo "Status=in_review; active=false\n";
