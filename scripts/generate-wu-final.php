<?php

declare(strict_types=1);

/**
 * Final WU cube generator.
 *
 * Konsep:
 * - 6 simbol unik.
 * - 5 master cube A-E yang tidak ekuivalen melalui rotasi.
 * - Semua orientasi soal berasal dari 24 proper cube rotations.
 * - Setiap soal hanya menampilkan 1 kubus.
 * - Opsi A-E mewakili 5 master cube.
 * - 1 example + 12 scored.
 * - Difficulty: 4 easy, 5 medium, 3 hard.
 *
 * File lama tetap dipertahankan namanya agar kompatibel
 * dengan dataset/media_ref yang sudah ada.
 */

$root = dirname(__DIR__);

$datasetDir = $root . '/database/data/ist-final-staging';
$wuJsonPath = $datasetDir . '/wu.json';
$manifestPath = $datasetDir . '/manifest.json';
$mediaMetadataPath = $datasetDir . '/media/metadata.json';
$checksumsPath = $datasetDir . '/checksums.json';

$mediaDir = $datasetDir . '/media/wu';
$exampleDir = $mediaDir . '/examples';
$questionDir = $mediaDir . '/questions';
$optionDir = $mediaDir . '/options';

foreach ([$exampleDir, $questionDir, $optionDir] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Tidak dapat membuat directory: {$dir}");
    }
}

if (!is_file($wuJsonPath)) {
    throw new RuntimeException("wu.json tidak ditemukan: {$wuJsonPath}");
}

$wu = json_decode(
    file_get_contents($wuJsonPath),
    true,
    512,
    JSON_THROW_ON_ERROR
);

if (!isset($wu['questions']) || !is_array($wu['questions'])) {
    throw new RuntimeException('Struktur questions pada wu.json tidak valid.');
}

/*
|--------------------------------------------------------------------------
| 1. Simbol
|--------------------------------------------------------------------------
|
| Simbol dibuat orientation-invariant supaya rotasi kubus tidak menambah
| persoalan orientasi simbol di dalam bidang sisi.
|
*/

$symbols = [
    'dot',
    'ring',
    'x',
    'plus',
    'diamond',
    'square',
];

$positions = ['U', 'D', 'F', 'B', 'L', 'R'];

/*
|--------------------------------------------------------------------------
| 2. Lima master cube
|--------------------------------------------------------------------------
|
| Kelima mapping berikut dipilih dari kelas rotasi yang berbeda.
|
| Lebih penting lagi, himpunan visible triple (U,F,R) dari masing-masing
| master saling tidak overlap. Artinya satu tampilan tiga sisi tidak akan
| valid untuk dua master sekaligus.
|
*/

$masters = [
    'A' => [
        'U' => 'dot',
        'D' => 'ring',
        'F' => 'x',
        'B' => 'plus',
        'L' => 'diamond',
        'R' => 'square',
    ],

    'B' => [
        'U' => 'dot',
        'D' => 'x',
        'F' => 'ring',
        'B' => 'diamond',
        'L' => 'plus',
        'R' => 'square',
    ],

    'C' => [
        'U' => 'dot',
        'D' => 'plus',
        'F' => 'ring',
        'B' => 'square',
        'L' => 'x',
        'R' => 'diamond',
    ],

    'D' => [
        'U' => 'dot',
        'D' => 'diamond',
        'F' => 'ring',
        'B' => 'plus',
        'L' => 'square',
        'R' => 'x',
    ],

    'E' => [
        'U' => 'dot',
        'D' => 'square',
        'F' => 'ring',
        'B' => 'x',
        'L' => 'diamond',
        'R' => 'plus',
    ],
];

/*
|--------------------------------------------------------------------------
| 3. Rotasi proper kubus
|--------------------------------------------------------------------------
*/

function rotateX(array $o): array
{
    return [
        'U' => $o['B'],
        'D' => $o['F'],
        'F' => $o['U'],
        'B' => $o['D'],
        'L' => $o['L'],
        'R' => $o['R'],
    ];
}

function rotateY(array $o): array
{
    return [
        'U' => $o['U'],
        'D' => $o['D'],
        'F' => $o['L'],
        'B' => $o['R'],
        'L' => $o['B'],
        'R' => $o['F'],
    ];
}

function rotateZ(array $o): array
{
    return [
        'U' => $o['R'],
        'D' => $o['L'],
        'F' => $o['F'],
        'B' => $o['B'],
        'L' => $o['U'],
        'R' => $o['D'],
    ];
}

/** Satu transformasi improper untuk membentuk kelas pencerminan kubus. */
function reflectCube(array $o): array
{
    return [
        'U' => $o['U'],
        'D' => $o['D'],
        'F' => $o['F'],
        'B' => $o['B'],
        'L' => $o['R'],
        'R' => $o['L'],
    ];
}

function orientationKey(array $o): string
{
    return implode('|', [
        $o['U'],
        $o['D'],
        $o['F'],
        $o['B'],
        $o['L'],
        $o['R'],
    ]);
}

/**
 * Menghasilkan tepat 24 proper rotations.
 *
 * Juga menyimpan jarak minimum berupa jumlah quarter-turn dari
 * orientasi canonical.
 */
function allRotationsWithDistance(array $initial): array
{
    $queue = [
        [
            'orientation' => $initial,
            'distance' => 0,
        ],
    ];

    $seen = [];
    $result = [];

    while ($queue !== []) {
        $current = array_shift($queue);

        $orientation = $current['orientation'];
        $distance = $current['distance'];

        $key = orientationKey($orientation);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;

        $result[] = [
            'orientation' => $orientation,
            'distance' => $distance,
        ];

        foreach ([
            rotateX($orientation),
            rotateY($orientation),
            rotateZ($orientation),
        ] as $next) {
            $nextKey = orientationKey($next);

            if (!isset($seen[$nextKey])) {
                $queue[] = [
                    'orientation' => $next,
                    'distance' => $distance + 1,
                ];
            }
        }
    }

    if (count($result) !== 24) {
        throw new RuntimeException(
            'Generator rotasi gagal: seharusnya menghasilkan 24 orientasi, ' .
            count($result) . ' ditemukan.'
        );
    }

    return $result;
}

function visibleTriple(array $orientation): string
{
    return implode('|', [
        $orientation['U'],
        $orientation['F'],
        $orientation['R'],
    ]);
}

/*
|--------------------------------------------------------------------------
| 4. Validasi 5 master
|--------------------------------------------------------------------------
*/

$masterRotations = [];
$masterTriples = [];
$masterRotationKeys = [];
$masterReflectionKeys = [];

foreach ($masters as $masterKey => $master) {
    $rotations = allRotationsWithDistance($master);

    $masterRotations[$masterKey] = $rotations;
    $masterRotationKeys[$masterKey] = array_fill_keys(array_map(
        static fn (array $rotation): string => orientationKey($rotation['orientation']),
        $rotations
    ), true);
    $masterReflectionKeys[$masterKey] = array_fill_keys(array_map(
        static fn (array $rotation): string => orientationKey($rotation['orientation']),
        allRotationsWithDistance(reflectCube($master))
    ), true);

    if (array_intersect_key($masterRotationKeys[$masterKey], $masterReflectionKeys[$masterKey]) !== []) {
        throw new RuntimeException("Master {$masterKey} tidak membedakan proper rotation dari pencerminan.");
    }

    $triples = [];

    foreach ($rotations as $rotation) {
        $triple = visibleTriple($rotation['orientation']);

        if (isset($triples[$triple])) {
            throw new RuntimeException(
                "Master {$masterKey} menghasilkan visible triple duplikat."
            );
        }

        $triples[$triple] = true;
    }

    if (count($triples) !== 24) {
        throw new RuntimeException(
            "Master {$masterKey} tidak memiliki tepat 24 visible triple."
        );
    }

    $masterTriples[$masterKey] = $triples;
}

/*
 * Harus zero-overlap antar master.
 *
 * Dengan begitu sebuah query tiga sisi selalu menunjuk tepat satu
 * master A-E.
 */
$masterKeys = array_keys($masters);

for ($i = 0; $i < count($masterKeys); $i++) {
    for ($j = $i + 1; $j < count($masterKeys); $j++) {
        $a = $masterKeys[$i];
        $b = $masterKeys[$j];

        $overlap = array_intersect_key(
            $masterTriples[$a],
            $masterTriples[$b]
        );

        if ($overlap !== []) {
            throw new RuntimeException(
                "Master {$a} dan {$b} mempunyai visible triple yang overlap."
            );
        }

        if (array_intersect_key($masterRotationKeys[$a], $masterRotationKeys[$b]) !== []) {
            throw new RuntimeException("Master {$a} dan {$b} ekuivalen melalui proper rotation.");
        }

        if (
            array_intersect_key($masterReflectionKeys[$a], $masterRotationKeys[$b]) !== []
            || array_intersect_key($masterReflectionKeys[$b], $masterRotationKeys[$a]) !== []
        ) {
            throw new RuntimeException("Master {$a} dan {$b} merupakan pasangan mirror-equivalent.");
        }
    }
}

/*
|--------------------------------------------------------------------------
| 5. SVG renderer
|--------------------------------------------------------------------------
*/

function svgEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function symbolSvg(
    string $symbol,
    float $cx,
    float $cy,
    float $scale = 1.0
): string {
    $stroke = '#18181b';
    $strokeWidth = 2.7 * $scale;

    switch ($symbol) {
        case 'dot':
            return sprintf(
                '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s"/>',
                $cx,
                $cy,
                5.8 * $scale,
                $stroke
            );

        case 'ring':
            return sprintf(
                '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="none" stroke="%s" stroke-width="%.2f"/>',
                $cx,
                $cy,
                10.0 * $scale,
                $stroke,
                $strokeWidth
            );

        case 'x':
            $d = 8.5 * $scale;

            return sprintf(
                '<path d="M %.2f %.2f L %.2f %.2f M %.2f %.2f L %.2f %.2f" fill="none" stroke="%s" stroke-width="%.2f" stroke-linecap="round"/>',
                $cx - $d,
                $cy - $d,
                $cx + $d,
                $cy + $d,
                $cx + $d,
                $cy - $d,
                $cx - $d,
                $cy + $d,
                $stroke,
                $strokeWidth
            );

        case 'plus':
            $d = 9.0 * $scale;

            return sprintf(
                '<path d="M %.2f %.2f H %.2f M %.2f %.2f V %.2f" fill="none" stroke="%s" stroke-width="%.2f" stroke-linecap="round"/>',
                $cx - $d,
                $cy,
                $cx + $d,
                $cx,
                $cy - $d,
                $cy + $d,
                $stroke,
                $strokeWidth
            );

        case 'diamond':
            $d = 10.0 * $scale;

            return sprintf(
                '<polygon points="%.2f,%.2f %.2f,%.2f %.2f,%.2f %.2f,%.2f" fill="none" stroke="%s" stroke-width="%.2f" stroke-linejoin="round"/>',
                $cx,
                $cy - $d,
                $cx + $d,
                $cy,
                $cx,
                $cy + $d,
                $cx - $d,
                $cy,
                $stroke,
                $strokeWidth
            );

        case 'square':
            $d = 7.5 * $scale;

            return sprintf(
                '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" rx="1.5" fill="%s"/>',
                $cx - $d,
                $cy - $d,
                $d * 2,
                $d * 2,
                $stroke
            );

        default:
            throw new RuntimeException("Simbol tidak dikenal: {$symbol}");
    }
}

function cubeSvg(array $orientation, string $role): string
{
    $top = $orientation['U'];
    $front = $orientation['F'];
    $right = $orientation['R'];

    $svg = [];

    $attributes = implode(' ', array_map(
        static fn (string $position): string =>
            'data-face-'.strtolower($position).'="'.svgEscape($orientation[$position]).'"',
        ['U', 'D', 'F', 'B', 'L', 'R']
    ));
    $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="180" viewBox="0 0 240 180" role="img" aria-hidden="true" data-wu-role="'.svgEscape($role).'" '.$attributes.'>';

    /*
     * Sedikit bayangan supaya bentuk tiga dimensi terbaca,
     * tetapi tetap minimal dan sesuai style media lama.
     */
    $svg[] = '<ellipse cx="120" cy="157" rx="64" ry="8" fill="#18181b" opacity="0.08"/>';

    /*
     * Top face.
     */
    $svg[] = '<polygon points="120,22 178,52 120,82 62,52" fill="#fafafa" stroke="#18181b" stroke-width="3" stroke-linejoin="round"/>';

    /*
     * Front/left-visible face.
     */
    $svg[] = '<polygon points="62,52 120,82 120,154 62,124" fill="#f4f4f5" stroke="#18181b" stroke-width="3" stroke-linejoin="round"/>';

    /*
     * Right-visible face.
     */
    $svg[] = '<polygon points="120,82 178,52 178,124 120,154" fill="#e4e4e7" stroke="#18181b" stroke-width="3" stroke-linejoin="round"/>';

    /*
     * Symbol centers.
     */
    $svg[] = symbolSvg($top, 120, 52, 1.0);
    $svg[] = symbolSvg($front, 91, 105, 0.95);
    $svg[] = symbolSvg($right, 149, 105, 0.95);

    $svg[] = '</svg>';

    return implode("\n", $svg) . "\n";
}

function writeFileStrict(string $path, string $contents): void
{
    $written = file_put_contents($path, $contents);

    if ($written === false) {
        throw new RuntimeException("Gagal menulis file: {$path}");
    }
}

function readJsonFileStrict(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("File JSON tidak ditemukan: {$path}");
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Gagal membaca file JSON: {$path}");
    }

    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($decoded)) {
        throw new RuntimeException("Struktur JSON tidak valid: {$path}");
    }

    return $decoded;
}

function writeJsonFileStrict(string $path, array $payload): void
{
    writeFileStrict(
        $path,
        json_encode(
            $payload,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_THROW_ON_ERROR
        ) . "\n"
    );
}

/*
|--------------------------------------------------------------------------
| 6. Utility memilih rotation berdasarkan distance
|--------------------------------------------------------------------------
*/

function rotationsAtDistance(array $rotations, int $distance): array
{
    return array_values(array_filter(
        $rotations,
        static fn (array $row): bool => $row['distance'] === $distance
    ));
}

function chooseRotation(
    array $masterRotations,
    string $masterKey,
    int $distance,
    int $variant = 0
): array {
    if (!isset($masterRotations[$masterKey])) {
        throw new RuntimeException("Master {$masterKey} tidak ditemukan.");
    }

    $available = rotationsAtDistance(
        $masterRotations[$masterKey],
        $distance
    );

    if ($available === []) {
        throw new RuntimeException(
            "Master {$masterKey} tidak mempunyai rotasi distance {$distance}."
        );
    }

    return $available[$variant % count($available)]['orientation'];
}

/**
 * Memilih rotasi pada sudut terlihat yang sama dengan master canonical.
 * Dengan demikian ketiga simbol pada target semuanya juga terlihat pada
 * reference, sehingga item dapat diselesaikan dari informasi visual peserta.
 */
function chooseVisibleCornerRotation(
    array $masterRotations,
    string $masterKey,
    int $distance
): array {
    if (!isset($masterRotations[$masterKey])) {
        throw new RuntimeException("Master {$masterKey} tidak ditemukan.");
    }

    $canonical = $masterRotations[$masterKey][0]['orientation'];
    $canonicalVisible = [
        $canonical['U'],
        $canonical['F'],
        $canonical['R'],
    ];
    sort($canonicalVisible, SORT_STRING);
    $matches = array_values(array_filter(
        $masterRotations[$masterKey],
        static function (array $rotation) use ($canonicalVisible, $distance): bool {
            if ($rotation['distance'] !== $distance) {
                return false;
            }

            $visible = [
                $rotation['orientation']['U'],
                $rotation['orientation']['F'],
                $rotation['orientation']['R'],
            ];
            sort($visible, SORT_STRING);

            return $visible === $canonicalVisible;
        }
    ));

    if (count($matches) !== 1) {
        throw new RuntimeException(
            "Master {$masterKey} harus mempunyai tepat satu visible-corner rotation pada distance {$distance}."
        );
    }

    return $matches[0]['orientation'];
}

/*
|--------------------------------------------------------------------------
| 7. Plan example dan 12 scored
|--------------------------------------------------------------------------
|
| Distance digunakan sebagai salah satu kontrol kompleksitas:
|
| Setiap target memakai salah satu dari tiga rotasi legal pada sudut terlihat
| master canonical (distance 0, 2, atau 4). Seluruh simbol yang dibandingkan
| karena itu benar-benar terlihat oleh peserta pada target dan reference.
|
| Semua query tetap diverifikasi terhadap seluruh 5 master.
|
*/

$examplePlan = [
    'master' => 'B',
    'distance' => 2,
    'variant' => 1,
];

$questionPlan = [
    1 => [
        'master' => 'C',
        'difficulty' => 'easy',
        'distance' => 0,
        'variant' => 0,
    ],
    2 => [
        'master' => 'A',
        'difficulty' => 'easy',
        'distance' => 2,
        'variant' => 1,
    ],
    3 => [
        'master' => 'D',
        'difficulty' => 'easy',
        'distance' => 0,
        'variant' => 2,
    ],
    4 => [
        'master' => 'B',
        'difficulty' => 'easy',
        'distance' => 0,
        'variant' => 0,
    ],

    5 => [
        'master' => 'E',
        'difficulty' => 'medium',
        'distance' => 2,
        'variant' => 0,
    ],
    6 => [
        'master' => 'C',
        'difficulty' => 'medium',
        'distance' => 2,
        'variant' => 2,
    ],
    7 => [
        'master' => 'D',
        'difficulty' => 'medium',
        'distance' => 2,
        'variant' => 4,
    ],
    8 => [
        'master' => 'A',
        'difficulty' => 'medium',
        'distance' => 4,
        'variant' => 1,
    ],
    9 => [
        'master' => 'C',
        'difficulty' => 'medium',
        'distance' => 4,
        'variant' => 5,
    ],

    10 => [
        'master' => 'E',
        'difficulty' => 'hard',
        'distance' => 4,
        'variant' => 0,
    ],
    11 => [
        'master' => 'B',
        'difficulty' => 'hard',
        'distance' => 4,
        'variant' => 2,
    ],
    12 => [
        'master' => 'D',
        'difficulty' => 'hard',
        'distance' => 4,
        'variant' => 3,
    ],
];

/*
|--------------------------------------------------------------------------
| 8. Validasi setiap query hanya cocok ke satu master
|--------------------------------------------------------------------------
*/

function matchingMasters(
    array $orientation,
    array $masterTriples
): array {
    $triple = visibleTriple($orientation);
    $matches = [];

    foreach ($masterTriples as $masterKey => $triples) {
        if (isset($triples[$triple])) {
            $matches[] = $masterKey;
        }
    }

    return $matches;
}

$exampleOrientation = chooseVisibleCornerRotation(
    $masterRotations,
    $examplePlan['master'],
    $examplePlan['distance']
);

$exampleMatches = matchingMasters(
    $exampleOrientation,
    $masterTriples
);

if ($exampleMatches !== [$examplePlan['master']]) {
    throw new RuntimeException(
        'Example WU ambigu atau salah master: ' .
        json_encode($exampleMatches)
    );
}

$resolvedQuestions = [];

foreach ($questionPlan as $number => $plan) {
    $orientation = chooseVisibleCornerRotation(
        $masterRotations,
        $plan['master'],
        $plan['distance']
    );

    $matches = matchingMasters(
        $orientation,
        $masterTriples
    );

    if ($matches !== [$plan['master']]) {
        throw new RuntimeException(
            "WU-S-" . str_pad((string) $number, 3, '0', STR_PAD_LEFT) .
            ' ambigu. Match: ' .
            json_encode($matches)
        );
    }

    $resolvedQuestions[$number] = [
        ...$plan,
        'orientation' => $orientation,
        'triple' => visibleTriple($orientation),
    ];
}

/*
 * Pastikan query scored tidak menduplikasi visible triple.
 */
$queryTriples = [];

foreach ($resolvedQuestions as $number => $plan) {
    if (isset($queryTriples[$plan['triple']])) {
        throw new RuntimeException(
            "Visible triple soal {$number} duplikat dengan soal " .
            $queryTriples[$plan['triple']] . '.'
        );
    }

    $queryTriples[$plan['triple']] = $number;
}

/*
|--------------------------------------------------------------------------
| 9. Generate SVG master A-E
|--------------------------------------------------------------------------
|
| Example options adalah master cube yang terlihat pada Instruction page.
|
| Scored option SVG memakai visual master yang sama dan ditampilkan kembali
| pada halaman Work sebagai lima reference A-E.
|
*/

foreach ($masters as $masterKey => $orientation) {
    $lower = strtolower($masterKey);

    /*
     * Master untuk example / halaman instruksi.
     */
    writeFileStrict(
        "{$exampleDir}/wu-example-001-option-{$lower}.svg",
        cubeSvg($orientation, 'master')
    );

    /*
     * Copy visual master yang sama ke opsi setiap scored question.
     */
    for ($number = 1; $number <= 12; $number++) {
        $q = str_pad((string) $number, 3, '0', STR_PAD_LEFT);

        writeFileStrict(
            "{$optionDir}/wu-q{$q}-option-{$lower}.svg",
            cubeSvg($orientation, 'master')
        );
    }
}

/*
|--------------------------------------------------------------------------
| 10. Generate example query
|--------------------------------------------------------------------------
*/

writeFileStrict(
    "{$exampleDir}/wu-example-001-reference.svg",
    cubeSvg($exampleOrientation, 'target')
);

/*
|--------------------------------------------------------------------------
| 11. Generate 12 scored query
|--------------------------------------------------------------------------
*/

foreach ($resolvedQuestions as $number => $plan) {
    $q = str_pad((string) $number, 3, '0', STR_PAD_LEFT);

    writeFileStrict(
        "{$questionDir}/wu-q{$q}-reference.svg",
        cubeSvg($plan['orientation'], 'target')
    );
}

/*
|--------------------------------------------------------------------------
| 12. Update wu.json
|--------------------------------------------------------------------------
*/

$instruction = implode("\n", [
    'Perhatikan lima kubus acuan A–E pada contoh.',
    'Bandingkan tiga simbol pada sisi atas, depan, dan kanan yang terlihat.',
    'Pada setiap soal hanya ditampilkan satu kubus.',
    'Tentukan kubus acuan A, B, C, D, atau E yang identik dengan kubus soal setelah diputar.',
    'Ketiga simbol pada kubus soal juga terlihat pada kubus acuan yang benar; perhatikan urutan sisi di sekitar sudut kubus.',
    'Kubus boleh diputar, tetapi tidak boleh dicerminkan.',
]);

$questionPrompt = 'Bandingkan tiga simbol yang terlihat. Tentukan kubus acuan A–E yang identik dengan kubus target setelah rotasi legal.';

$examplePrompt = 'Bandingkan tiga simbol yang terlihat pada kubus contoh dengan kubus acuan A–E, lalu pilih rotasi legal yang identik.';

$wu['instruction_content'] = $instruction;

$foundExample = false;
$foundScored = [];

foreach ($wu['questions'] as &$question) {
    $logicalId = $question['logical_id'] ?? null;

    /*
     * Example.
     */
    if ($logicalId === 'WU-E-001') {
        $foundExample = true;

        $correctMaster = $examplePlan['master'];

        $question['prompt'] = $examplePrompt;
        $question['explanation'] =
            "Jawaban yang benar adalah kubus {$correctMaster}. " .
            'Tiga simbol terlihat sama dan urutannya di sekitar sudut ' .
            'dipertahankan oleh proper rotation, bukan pencerminan.';

        $question['difficulty_target'] = null;

        foreach ($question['options'] as &$option) {
            $key = strtoupper((string) ($option['key'] ?? ''));

            $option['text'] = null;
            $option['correct'] = $key === $correctMaster;
            $option['score'] = $key === $correctMaster ? 1 : 0;
        }
        unset($option);

        $question['media']['prompt_ref'] =
            'wu-example-001-reference';

        $question['metadata']['internal']['rationale'] =
            "Master {$correctMaster}; visible corner {$examplePlan['distance']} quarter-turn. " .
            'Ketiga simbol terlihat pada reference dan target; 24 proper rotations hanya cocok dengan satu master.';
        $question['metadata']['internal']['matching_masters'] = [$correctMaster];
        $question['metadata']['internal']['proper_rotations_checked'] = 24;
        $question['metadata']['internal']['reflection_used'] = false;

        continue;
    }

    /*
     * Scored.
     */
    if (
        is_string($logicalId) &&
        preg_match('/^WU-S-(\d{3})$/', $logicalId, $matches)
    ) {
        $number = (int) $matches[1];

        if (!isset($resolvedQuestions[$number])) {
            throw new RuntimeException(
                "Plan tidak ditemukan untuk {$logicalId}."
            );
        }

        $plan = $resolvedQuestions[$number];
        $correctMaster = $plan['master'];

        $foundScored[$number] = true;

        $question['prompt'] = $questionPrompt;
        $question['explanation'] = null;
        $question['difficulty_target'] = $plan['difficulty'];

        foreach ($question['options'] as &$option) {
            $key = strtoupper((string) ($option['key'] ?? ''));

            $option['text'] = null;
            $option['correct'] = $key === $correctMaster;
            $option['score'] = $key === $correctMaster ? 1 : 0;
        }
        unset($option);

        $question['media']['prompt_ref'] =
            'wu-' .
            str_pad((string) $number, 3, '0', STR_PAD_LEFT) .
            '-reference';

        $question['metadata']['internal']['rationale'] =
            "Master {$correctMaster}; proper rotation distance {$plan['distance']}. " .
            "Visible triple {$plan['triple']} memakai tiga simbol yang juga terlihat pada reference dan hanya valid untuk master {$correctMaster}.";
        $question['metadata']['internal']['matching_masters'] = [$correctMaster];
        $question['metadata']['internal']['proper_rotations_checked'] = 24;
        $question['metadata']['internal']['reflection_used'] = false;
    }
}
unset($question);

if (!$foundExample) {
    throw new RuntimeException('WU-E-001 tidak ditemukan pada wu.json.');
}

if (count($foundScored) !== 12) {
    throw new RuntimeException(
        'Seharusnya 12 scored WU ditemukan, ' .
        count($foundScored) .
        ' ditemukan.'
    );
}

/*
|--------------------------------------------------------------------------
| 13. Final structural checks
|--------------------------------------------------------------------------
*/

$difficultyCount = [
    'easy' => 0,
    'medium' => 0,
    'hard' => 0,
];

$answerKey = [];

foreach ($wu['questions'] as $question) {
    if (($question['kind'] ?? null) !== 'scored') {
        continue;
    }

    $difficulty = $question['difficulty_target'] ?? null;

    if (isset($difficultyCount[$difficulty])) {
        $difficultyCount[$difficulty]++;
    }

    $correctOptions = array_values(array_filter(
        $question['options'] ?? [],
        static fn (array $option): bool =>
            ($option['correct'] ?? false) === true &&
            ($option['score'] ?? 0) === 1
    ));

    if (count($correctOptions) !== 1) {
        throw new RuntimeException(
            "{$question['logical_id']} harus mempunyai tepat satu jawaban benar."
        );
    }

    $answerKey[$question['logical_id']] =
        $correctOptions[0]['key'];
}

$expectedDifficulty = [
    'easy' => 4,
    'medium' => 5,
    'hard' => 3,
];

if ($difficultyCount !== $expectedDifficulty) {
    throw new RuntimeException(
        'Distribusi difficulty tidak sesuai: ' .
        json_encode($difficultyCount)
    );
}

/*
|--------------------------------------------------------------------------
| 14. Write wu.json
|--------------------------------------------------------------------------
*/

$json = json_encode(
    $wu,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_THROW_ON_ERROR
);

writeFileStrict(
    $wuJsonPath,
    $json . "\n"
);

/*
|--------------------------------------------------------------------------
| 15. Synchronize media metadata, manifest fingerprints, and checksums
|--------------------------------------------------------------------------
*/

$mediaMetadata = readJsonFileStrict($mediaMetadataPath);

if (!isset($mediaMetadata['media']) || !is_array($mediaMetadata['media'])) {
    throw new RuntimeException('Struktur media/metadata.json tidak valid.');
}

$wuMediaCount = 0;

foreach ($mediaMetadata['media'] as &$mediaRecord) {
    if (($mediaRecord['subtest_code'] ?? null) !== 'WU') {
        continue;
    }

    $relativePath = $mediaRecord['relative_path'] ?? null;

    if (!is_string($relativePath) || !str_starts_with($relativePath, 'media/wu/')) {
        throw new RuntimeException('Path metadata media WU tidak valid.');
    }

    $absolutePath = $datasetDir . '/' . $relativePath;

    if (!is_file($absolutePath)) {
        throw new RuntimeException("Media WU tidak ditemukan: {$relativePath}");
    }

    $byteSize = filesize($absolutePath);
    $sha256 = hash_file('sha256', $absolutePath);

    if ($byteSize === false || $sha256 === false) {
        throw new RuntimeException("Media WU tidak dapat diperiksa: {$relativePath}");
    }

    $mediaRecord['byte_size'] = $byteSize;
    $mediaRecord['sha256'] = $sha256;
    $wuMediaCount++;
}
unset($mediaRecord);

if ($wuMediaCount !== 78) {
    throw new RuntimeException(
        "Metadata harus memuat tepat 78 media WU, ditemukan {$wuMediaCount}."
    );
}

writeJsonFileStrict($mediaMetadataPath, $mediaMetadata);

$manifest = readJsonFileStrict($manifestPath);
$subtestHashes = [];

foreach ($manifest['subtests'] ?? [] as $subtestEntry) {
    $file = $subtestEntry['file'] ?? null;

    if (!is_string($file) || !is_file($datasetDir . '/' . $file)) {
        throw new RuntimeException('Manifest mempunyai file subtest yang tidak valid.');
    }

    $subtestHashes[$file] = hash_file('sha256', $datasetDir . '/' . $file);
}

ksort($subtestHashes, SORT_STRING);

$manifest['content_fingerprint'] = hash(
    'sha256',
    implode("\n", array_map(
        static fn (string $path, string $hash): string => "{$path}:{$hash}",
        array_keys($subtestHashes),
        array_values($subtestHashes)
    ))
);

$mediaAggregateParts = array_map(
    static fn (array $record): string =>
        $record['logical_id'] . ':' . $record['sha256'],
    $mediaMetadata['media']
);
sort($mediaAggregateParts, SORT_STRING);

$manifest['media_checksum_aggregate'] = hash(
    'sha256',
    implode("\n", $mediaAggregateParts)
);
$manifest['media']['count'] = count($mediaMetadata['media']);

writeJsonFileStrict($manifestPath, $manifest);

$checksums = readJsonFileStrict($checksumsPath);
$checksumFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $datasetDir,
        RecursiveDirectoryIterator::SKIP_DOTS
    )
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $relativePath = str_replace(
        '\\',
        '/',
        substr($file->getPathname(), strlen($datasetDir) + 1)
    );

    if ($relativePath === 'checksums.json') {
        continue;
    }

    $checksumFiles[$relativePath] = hash_file(
        'sha256',
        $file->getPathname()
    );
}

ksort($checksumFiles, SORT_STRING);
$checksums['algorithm'] = 'sha256';
$checksums['files'] = $checksumFiles;

writeJsonFileStrict($checksumsPath, $checksums);

/*
|--------------------------------------------------------------------------
| 16. Summary
|--------------------------------------------------------------------------
*/

$svgFiles = [];

foreach ([
    $exampleDir,
    $questionDir,
    $optionDir,
] as $dir) {
    foreach (glob($dir . '/*.svg') ?: [] as $file) {
        $svgFiles[] = $file;
    }
}

sort($svgFiles);

echo PHP_EOL;
echo "WU FINAL GENERATOR: SUCCESS" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Master cube       : 5 (A-E)" . PHP_EOL;
echo "Proper rotations  : 24 per master" . PHP_EOL;
echo "Example           : 1" . PHP_EOL;
echo "Scored            : 12" . PHP_EOL;
echo "Easy/Medium/Hard  : 4 / 5 / 3" . PHP_EOL;
echo "SVG WU total      : " . count($svgFiles) . PHP_EOL;
echo PHP_EOL;

echo "Example answer     : {$examplePlan['master']}" . PHP_EOL;
echo PHP_EOL;

echo "Scored answer key:" . PHP_EOL;

foreach ($answerKey as $logicalId => $key) {
    echo "  {$logicalId} = {$key}" . PHP_EOL;
}

echo PHP_EOL;
echo "Semua query diverifikasi terhadap 5 master." . PHP_EOL;
echo "Tidak ada visible triple ambigu antar master." . PHP_EOL;
echo "Tidak ada master proper-rotation atau mirror-equivalent." . PHP_EOL;
echo "Semua target memakai tiga simbol yang terlihat pada reference." . PHP_EOL;
echo "Metadata, fingerprint, dan checksum staging tersinkronisasi." . PHP_EOL;
echo PHP_EOL;
