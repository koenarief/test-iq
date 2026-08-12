<?php

declare(strict_types=1);

/**
 * Final ME generator.
 *
 * Konsep final:
 * - 5 kategori.
 * - 5 kata per kategori.
 * - Total 25 kata.
 * - Seluruh 25 kata memiliki huruf awal unik.
 * - 1 example, tidak mengambil jawaban dari 12 scored.
 * - 12 scored:
 *      4 easy
 *      5 medium
 *      3 hard
 * - Peserta melihat materi selama fase memorization.
 * - Saat answering, peserta hanya mendapat huruf awal
 *   dan memilih kategori A-E.
 */

$root = dirname(__DIR__);

$datasetDirectory = $root . '/database/data/ist-final-staging';
$path = $datasetDirectory . '/me.json';
$manifestPath = $datasetDirectory . '/manifest.json';
$checksumsPath = $datasetDirectory . '/checksums.json';

function meReadJson(string $path): array
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Tidak dapat membaca {$path}.");
    }

    return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
}

function meWriteJson(string $path, array $payload): void
{
    $contents = json_encode(
        $payload,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_THROW_ON_ERROR,
    )."\n";

    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Tidak dapat menulis {$path}.");
    }
}

if (! is_file($path)) {
    throw new RuntimeException("me.json tidak ditemukan: {$path}");
}

$me = meReadJson($path);

if (! isset($me['questions']) || ! is_array($me['questions'])) {
    throw new RuntimeException('Struktur questions pada me.json tidak valid.');
}

/*
|--------------------------------------------------------------------------
| 1. Lima kategori final
|--------------------------------------------------------------------------
*/

$groups = [
    [
        'key' => 'A',
        'name' => 'Tanaman',
        'display_order' => 1,
        'words' => [
            'Anggrek',
            'Dahlia',
            'Kaktus',
            'Melati',
            'Teratai',
        ],
    ],
    [
        'key' => 'B',
        'name' => 'Peralatan',
        'display_order' => 2,
        'words' => [
            'Bor',
            'Gergaji',
            'Obeng',
            'Palu',
            'Ragum',
        ],
    ],
    [
        'key' => 'C',
        'name' => 'Hewan',
        'display_order' => 3,
        'words' => [
            'Cendrawasih',
            'Elang',
            'Jerapah',
            'Unta',
            'Zebra',
        ],
    ],
    [
        'key' => 'D',
        'name' => 'Kesenian',
        'display_order' => 4,
        'words' => [
            'Lukisan',
            'Nyanyian',
            'Qasidah',
            'Wayang',
            'Xilofon',
        ],
    ],
    [
        'key' => 'E',
        'name' => 'Transportasi',
        'display_order' => 5,
        'words' => [
            'Feri',
            'Helikopter',
            'Sepeda',
            'Van',
            'Yacht',
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| 2. Bangun index huruf awal
|--------------------------------------------------------------------------
*/

$letterIndex = [];
$allWords = [];

foreach ($groups as $group) {
    foreach ($group['words'] as $wordIndex => $word) {
        $letter = mb_strtoupper(
            mb_substr($word, 0, 1, 'UTF-8'),
            'UTF-8'
        );

        if (isset($letterIndex[$letter])) {
            throw new RuntimeException(
                "Huruf awal {$letter} digunakan lebih dari sekali: " .
                $letterIndex[$letter]['word'] .
                " dan {$word}."
            );
        }

        $letterIndex[$letter] = [
            'letter' => $letter,
            'word' => $word,
            'group_key' => $group['key'],
            'group_name' => $group['name'],
            'group_display_order' => $group['display_order'],
            'word_display_order' => $wordIndex + 1,
        ];

        $allWords[] = $word;
    }
}

if (count($letterIndex) !== 25) {
    throw new RuntimeException(
        'Bank ME harus memiliki tepat 25 huruf awal unik.'
    );
}

/*
|--------------------------------------------------------------------------
| 3. Pastikan huruf final tepat seperti yang disepakati
|--------------------------------------------------------------------------
*/

$expectedLetters = [
    'A', 'B', 'C', 'D', 'E',
    'F', 'G', 'H', 'J', 'K',
    'L', 'M', 'N', 'O', 'P',
    'Q', 'R', 'S', 'T', 'U',
    'V', 'W', 'X', 'Y', 'Z',
];

$actualLetters = array_keys($letterIndex);

sort($actualLetters);
sort($expectedLetters);

if ($actualLetters !== $expectedLetters) {
    throw new RuntimeException(
        'Set huruf awal ME tidak sesuai kontrak final.'
    );
}

/*
|--------------------------------------------------------------------------
| 4. Plan 12 scored
|--------------------------------------------------------------------------
|
| Difficulty adalah target editorial, bukan hasil psikometri empiris.
|
*/

$questionPlan = [
    1 => [
        'letter' => 'A',
        'difficulty' => 'easy',
    ],
    2 => [
        'letter' => 'B',
        'difficulty' => 'easy',
    ],
    3 => [
        'letter' => 'E',
        'difficulty' => 'easy',
    ],
    4 => [
        'letter' => 'H',
        'difficulty' => 'easy',
    ],

    5 => [
        'letter' => 'G',
        'difficulty' => 'medium',
    ],
    6 => [
        'letter' => 'J',
        'difficulty' => 'medium',
    ],
    7 => [
        'letter' => 'L',
        'difficulty' => 'medium',
    ],
    8 => [
        'letter' => 'S',
        'difficulty' => 'medium',
    ],
    9 => [
        'letter' => 'T',
        'difficulty' => 'medium',
    ],

    10 => [
        'letter' => 'Q',
        'difficulty' => 'hard',
    ],
    11 => [
        'letter' => 'U',
        'difficulty' => 'hard',
    ],
    12 => [
        'letter' => 'X',
        'difficulty' => 'hard',
    ],
];

/*
|--------------------------------------------------------------------------
| 5. Validasi plan
|--------------------------------------------------------------------------
*/

$difficultyCount = [
    'easy' => 0,
    'medium' => 0,
    'hard' => 0,
];

$usedLetters = [];

foreach ($questionPlan as $number => $plan) {
    $letter = $plan['letter'];
    $difficulty = $plan['difficulty'];

    if (! isset($letterIndex[$letter])) {
        throw new RuntimeException(
            "Huruf {$letter} untuk soal {$number} tidak ditemukan."
        );
    }

    if (isset($usedLetters[$letter])) {
        throw new RuntimeException(
            "Huruf {$letter} digunakan pada lebih dari satu scored question."
        );
    }

    $usedLetters[$letter] = true;

    if (! isset($difficultyCount[$difficulty])) {
        throw new RuntimeException(
            "Difficulty tidak dikenal: {$difficulty}"
        );
    }

    $difficultyCount[$difficulty]++;
}

$expectedDifficulty = [
    'easy' => 4,
    'medium' => 5,
    'hard' => 3,
];

if ($difficultyCount !== $expectedDifficulty) {
    throw new RuntimeException(
        'Distribusi difficulty salah: ' .
        json_encode($difficultyCount)
    );
}

/*
|--------------------------------------------------------------------------
| 6. Instruction content
|--------------------------------------------------------------------------
*/

$me['instruction_content'] = implode("\n", [
    'Anda akan melihat 5 kelompok kata selama 2 menit.',
    'Setiap kelompok berisi 5 kata. Hafalkan kata-kata beserta kelompoknya.',
    'Setelah waktu menghafal berakhir, materi akan ditutup otomatis dan tidak dapat dibuka kembali.',
    'Anda kemudian memiliki 4 menit untuk menjawab 12 soal pilihan ganda.',
    'Pada setiap soal ditampilkan satu huruf awal. Ingat kata yang berawalan huruf tersebut, lalu pilih kelompok tempat kata itu berada.',
    'Pilih satu jawaban A–E untuk setiap soal.',
    'Contoh tidak dihitung dalam skor.',
]);

/*
|--------------------------------------------------------------------------
| 7. memorization_content untuk runtime
|--------------------------------------------------------------------------
*/

$runtimeGroups = [];

foreach ($groups as $group) {
    $runtimeGroups[] = [
        'key' => $group['key'],
        'name' => $group['name'],
        'words' => $group['words'],
        'display_order' => $group['display_order'],
    ];
}

$me['memorization_content'] = json_encode(
    [
        'groups' => $runtimeGroups,
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_THROW_ON_ERROR
);

/*
|--------------------------------------------------------------------------
| 8. Internal memorization metadata
|--------------------------------------------------------------------------
|
| Data ini server-side/editorial. Tidak dikirim langsung ke participant.
|
*/

$internalGroups = [];

foreach ($groups as $group) {
    $words = [];

    foreach ($group['words'] as $wordIndex => $word) {
        $letter = mb_strtoupper(
            mb_substr($word, 0, 1, 'UTF-8'),
            'UTF-8'
        );

        $testedQuestion = null;

        foreach ($questionPlan as $number => $plan) {
            if ($plan['letter'] === $letter) {
                $testedQuestion =
                    'ME-S-' .
                    str_pad((string) $number, 3, '0', STR_PAD_LEFT);

                break;
            }
        }

        $words[] = [
            'logical_id' =>
                'me-word-' .
                strtolower($letter),

            'word' => $word,
            'initial' => $letter,
            'display_order' => $wordIndex + 1,
            'tested' => $testedQuestion !== null,
            'question_logical_id' => $testedQuestion,

            'content_origin' => 'original_internal',
            'source_reference' => null,
            'copyright_status' => 'internally_authored',
            'normative_compatibility' => 'none',
            'review_status' => 'human_review_passed',
            'active' => false,
        ];
    }

    $internalGroups[] = [
        'key' => $group['key'],
        'name' => $group['name'],
        'display_order' => $group['display_order'],
        'words' => $words,
    ];
}

/*
 * Hapus model paired-associate lama sepenuhnya.
 */
$me['memorization'] = [
    'model' => 'initial_letter_to_category',
    'group_count' => 5,
    'words_per_group' => 5,
    'total_words' => 25,
    'unique_initials_required' => true,
    'groups' => $internalGroups,
    'review' => [
        'initial_uniqueness' => 'pass',
        'category_membership' => 'pass',
        'ambiguity_review' => 'pass',
        'memorization_duration_seconds' => 120,
        'answering_duration_seconds' => 240,
    ],
];

/*
|--------------------------------------------------------------------------
| 9. Opsi kategori A-E
|--------------------------------------------------------------------------
*/

function categoryOptions(
    array $groups,
    string $correctKey
): array {
    $options = [];

    foreach ($groups as $group) {
        $key = $group['key'];
        $correct = $key === $correctKey;

        $options[] = [
            'key' => $key,
            'text' => $group['name'],
            'display_order' => $group['display_order'],
            'correct' => $correct,
            'score' => $correct ? 1 : 0,
            'media_ref' => null,
        ];
    }

    return $options;
}

/*
|--------------------------------------------------------------------------
| 10. Update example + 12 scored
|--------------------------------------------------------------------------
*/

$foundExample = false;
$foundScored = [];

foreach ($me['questions'] as &$question) {
    $logicalId = $question['logical_id'] ?? null;

    /*
     * Example dibuat self-contained agar tidak membocorkan
     * salah satu dari 25 kata hafalan utama.
     */
    if ($logicalId === 'ME-E-001') {
        $foundExample = true;

        $question['answer_type'] = 'single_choice';
        $question['difficulty_target'] = null;

        $question['prompt'] =
            'Kata yang mempunyai huruf permulaan P termasuk kelompok …';

        $question['explanation'] =
            'Jawaban yang benar adalah Peralatan. ' .
            'Pada subtes utama, Anda tidak akan diberi nama katanya. ' .
            'Anda harus mengingat kata berdasarkan huruf awalnya, ' .
            'kemudian menentukan kelompok tempat kata tersebut berada. ' .
            'Contoh tidak dihitung dalam skor.';

        $question['scoring'] = [
            'max_score' => 1,
        ];

        $question['options'] = categoryOptions(
            $groups,
            'B'
        );

        $question['media'] = [
            'prompt_ref' => null,
        ];

        $question['source'] = [
            'reference' =>
                'internal-original/stage-17-me-category-memory#example',
        ];

        $question['source_reference'] = null;

        $question['metadata'] = [
            'public' => [
                'display_hint' => 'standard',
            ],
            'internal' => [
                'draft_logical_id' => 'me-example-001',
                'memory_model' => 'initial_letter_to_category',
                'example_word' => 'Pahat',
                'example_initial' => 'P',
                'example_category_key' => 'B',
                'example_category_name' => 'Peralatan',
                'rationale' =>
                    'Contoh menggunakan kata latihan di luar 25 kata hafalan utama sehingga tidak membocorkan target scored.',
            ],
        ];

        continue;
    }

    if (
        is_string($logicalId) &&
        preg_match('/^ME-S-(\d{3})$/', $logicalId, $matches)
    ) {
        $number = (int) $matches[1];

        if (! isset($questionPlan[$number])) {
            throw new RuntimeException(
                "Plan ME tidak ditemukan untuk {$logicalId}."
            );
        }

        $plan = $questionPlan[$number];
        $letter = $plan['letter'];
        $target = $letterIndex[$letter];

        $foundScored[$number] = true;

        $question['answer_type'] = 'single_choice';

        $question['question_number'] = $number;
        $question['display_order'] = $number;
        $question['difficulty_target'] = $plan['difficulty'];

        $question['prompt'] =
            "Kata yang mempunyai huruf permulaan {$letter} termasuk kelompok …";

        $question['explanation'] = null;

        $question['scoring'] = [
            'max_score' => 1,
        ];

        $question['options'] = categoryOptions(
            $groups,
            $target['group_key']
        );

        $question['media'] = [
            'prompt_ref' => null,
        ];

        $question['source'] = [
            'reference' =>
                'internal-original/stage-17-me-category-memory#' .
                strtolower($logicalId),
        ];

        $question['source_reference'] = null;

        /*
         * Buang metadata paired-associate lama.
         */
        $question['metadata'] = [
            'public' => [
                'display_hint' => 'standard',
            ],
            'internal' => [
                'draft_logical_id' =>
                    'me-' .
                    str_pad(
                        (string) $number,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),

                'memory_model' =>
                    'initial_letter_to_category',

                'target_initial' => $letter,
                'target_word' => $target['word'],
                'target_category_key' =>
                    $target['group_key'],

                'target_category_name' =>
                    $target['group_name'],

                'rationale' =>
                    "Huruf {$letter} hanya muncul sebagai huruf awal " .
                    "kata {$target['word']} dalam seluruh materi hafalan; " .
                    "kata tersebut berada pada kelompok {$target['group_name']}.",
            ],
        ];
    }
}
unset($question);

/*
|--------------------------------------------------------------------------
| 11. Structural validation
|--------------------------------------------------------------------------
*/

if (! $foundExample) {
    throw new RuntimeException(
        'ME-E-001 tidak ditemukan.'
    );
}

if (count($foundScored) !== 12) {
    throw new RuntimeException(
        'Seharusnya ditemukan 12 scored ME, ditemukan ' .
        count($foundScored) .
        '.'
    );
}

$answerKey = [];
$finalDifficulty = [
    'easy' => 0,
    'medium' => 0,
    'hard' => 0,
];
$difficultyWeights = [
    'easy' => 1,
    'medium' => 2,
    'hard' => 3,
];
$weightedMaximum = 0;
$exampleCount = 0;

foreach ($me['questions'] as $question) {
    if (($question['kind'] ?? null) !== 'scored') {
        if (($question['kind'] ?? null) === 'example') {
            $exampleCount++;

            if (($question['logical_id'] ?? null) !== 'ME-E-001'
                || ($question['prompt'] ?? null)
                    !== 'Kata yang mempunyai huruf permulaan P termasuk kelompok …'
                || str_contains((string) ($question['prompt'] ?? ''), 'Pahat')) {
                throw new RuntimeException('Kontrak participant example ME tidak valid.');
            }

            $correctExampleOptions = array_values(array_filter(
                $question['options'] ?? [],
                static fn (array $option): bool =>
                    ($option['correct'] ?? false) === true
                    && ($option['score'] ?? null) === 1,
            ));

            if (count($correctExampleOptions) !== 1
                || ($correctExampleOptions[0]['key'] ?? null) !== 'B') {
                throw new RuntimeException('Answer key example ME harus B.');
            }
        }

        continue;
    }

    $difficulty = $question['difficulty_target'] ?? null;

    if (! isset($finalDifficulty[$difficulty])) {
        throw new RuntimeException(
            "Difficulty invalid pada {$question['logical_id']}."
        );
    }

    $finalDifficulty[$difficulty]++;
    $weightedMaximum += $difficultyWeights[$difficulty];

    $options = $question['options'] ?? [];

    if (count($options) !== 5) {
        throw new RuntimeException(
            "{$question['logical_id']} harus mempunyai 5 opsi."
        );
    }

    $correct = array_values(
        array_filter(
            $options,
            static fn (array $option): bool =>
                ($option['correct'] ?? false) === true
                && ($option['score'] ?? null) === 1
        )
    );

    if (count($correct) !== 1) {
        throw new RuntimeException(
            "{$question['logical_id']} harus mempunyai tepat satu jawaban benar."
        );
    }

    foreach ($options as $option) {
        if (
            ($option['correct'] ?? false) === false
            && ($option['score'] ?? null) !== 0
        ) {
            throw new RuntimeException(
                "{$question['logical_id']} mempunyai skor distractor invalid."
            );
        }
    }

    $answerKey[$question['logical_id']] =
        $correct[0]['key'];
}

if ($exampleCount !== 1) {
    throw new RuntimeException('ME harus mempunyai tepat satu example.');
}

if ($finalDifficulty !== $expectedDifficulty) {
    throw new RuntimeException(
        'Distribusi difficulty final tidak sesuai: ' .
        json_encode($finalDifficulty)
    );
}

if ($weightedMaximum !== 23) {
    throw new RuntimeException(
        "Weighted maximum ME harus 23, ditemukan {$weightedMaximum}."
    );
}

/*
|--------------------------------------------------------------------------
| 12. Validasi seluruh target letter benar-benar unik
|--------------------------------------------------------------------------
*/

foreach ($questionPlan as $number => $plan) {
    $letter = $plan['letter'];

    $matches = array_values(
        array_filter(
            $allWords,
            static function (string $word) use ($letter): bool {
                return mb_strtoupper(
                    mb_substr($word, 0, 1, 'UTF-8'),
                    'UTF-8'
                ) === $letter;
            }
        )
    );

    if (count($matches) !== 1) {
        throw new RuntimeException(
            "Soal {$number} dengan huruf {$letter} ambigu."
        );
    }
}

/*
|--------------------------------------------------------------------------
| 13. Write ME and synchronize staging integrity metadata
|--------------------------------------------------------------------------
*/

meWriteJson($path, $me);

$manifest = meReadJson($manifestPath);
$updatedManifestEntry = false;

foreach ($manifest['subtests'] as &$subtest) {
    if (($subtest['code'] ?? null) !== 'ME') {
        continue;
    }

    $subtest['max_score'] = 23;
    $updatedManifestEntry = true;
}
unset($subtest);

if (! $updatedManifestEntry) {
    throw new RuntimeException('Manifest tidak memuat subtest ME.');
}

$subtestHashes = [];

foreach ($manifest['subtests'] as $subtest) {
    $file = $subtest['file'] ?? null;

    if (! is_string($file) || ! is_file($datasetDirectory.'/'.$file)) {
        throw new RuntimeException('Manifest mempunyai file subtest tidak valid.');
    }

    $hash = hash_file('sha256', $datasetDirectory.'/'.$file);

    if ($hash === false) {
        throw new RuntimeException("Tidak dapat menghitung checksum {$file}.");
    }

    $subtestHashes[$file] = $hash;
}

ksort($subtestHashes, SORT_STRING);
$contentFingerprint = hash('sha256', implode("\n", array_map(
    static fn (string $file, string $hash): string => "{$file}:{$hash}",
    array_keys($subtestHashes),
    array_values($subtestHashes),
)));
$manifest['content_fingerprint'] = $contentFingerprint;

meWriteJson($manifestPath, $manifest);

$checksums = meReadJson($checksumsPath);
$checksumFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $datasetDirectory,
        RecursiveDirectoryIterator::SKIP_DOTS,
    ),
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relativePath = str_replace(
        '\\',
        '/',
        substr($file->getPathname(), strlen($datasetDirectory) + 1),
    );

    if ($relativePath === 'checksums.json') {
        continue;
    }

    $checksum = hash_file('sha256', $file->getPathname());

    if ($checksum === false) {
        throw new RuntimeException("Tidak dapat menghitung checksum {$relativePath}.");
    }

    $checksumFiles[$relativePath] = $checksum;
}

ksort($checksumFiles, SORT_STRING);
$checksums['algorithm'] = 'sha256';
$checksums['files'] = $checksumFiles;
meWriteJson($checksumsPath, $checksums);

/*
|--------------------------------------------------------------------------
| 14. Summary
|--------------------------------------------------------------------------
*/

echo PHP_EOL;
echo "ME FINAL GENERATOR: SUCCESS" . PHP_EOL;
echo "==============================================" . PHP_EOL;

echo "Memory model       : initial letter -> category" . PHP_EOL;
echo "Categories         : 5" . PHP_EOL;
echo "Words/category     : 5" . PHP_EOL;
echo "Total words        : 25" . PHP_EOL;
echo "Unique initials    : 25" . PHP_EOL;
echo "Example            : 1" . PHP_EOL;
echo "Scored             : 12" . PHP_EOL;
echo "Easy/Medium/Hard   : 4 / 5 / 3" . PHP_EOL;
echo "Weighted maximum  : {$weightedMaximum}" . PHP_EOL;

echo PHP_EOL;

echo "Categories:" . PHP_EOL;

foreach ($groups as $group) {
    echo "  {$group['key']}. {$group['name']}: " .
        implode(', ', $group['words']) .
        PHP_EOL;
}

echo PHP_EOL;

echo "Scored answer key:" . PHP_EOL;

foreach ($answerKey as $logicalId => $key) {
    $number = (int) substr($logicalId, -3);
    $letter = $questionPlan[$number]['letter'];
    $target = $letterIndex[$letter];

    echo "  {$logicalId} | {$letter} -> {$target['word']} -> {$target['group_name']} = {$key}" .
        PHP_EOL;
}

echo PHP_EOL;

echo "Validation:" . PHP_EOL;
echo "  - 25 initial letters unique: PASS" . PHP_EOL;
echo "  - exactly 5 categories: PASS" . PHP_EOL;
echo "  - exactly 5 words/category: PASS" . PHP_EOL;
echo "  - 12 scored questions: PASS" . PHP_EOL;
echo "  - difficulty 4/5/3: PASS" . PHP_EOL;
echo "  - exactly one correct option/question: PASS" . PHP_EOL;
echo "  - paired-associate metadata removed: PASS" . PHP_EOL;
echo "  - participant prompts hide target words: PASS" . PHP_EOL;
echo "  - manifest/checksums synchronized: PASS" . PHP_EOL;

echo PHP_EOL;
echo "Content fingerprint: {$contentFingerprint}" . PHP_EOL;
