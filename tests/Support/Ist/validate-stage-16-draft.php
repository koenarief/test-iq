<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 3);
$draftPath = $projectRoot.'/docs/ist/content-drafts/stage-16-me-data-draft.json';
$productionPath = $projectRoot.'/database/data/ist-final/me.json';
$errors = [];

$fail = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

$raw = @file_get_contents($draftPath);

if (! is_string($raw)) {
    fwrite(STDERR, "Draft Tahap 16 tidak dapat dibaca.\n");
    exit(1);
}

try {
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, "JSON draft tidak valid: {$exception->getMessage()}\n");
    exit(1);
}

if (! is_array($data)) {
    fwrite(STDERR, "Root draft harus object JSON.\n");
    exit(1);
}

if (is_file($productionPath)) {
    $fail('Dataset produksi ME tidak boleh ada pada Tahap 16');
}

if (preg_match('/Soal IST\.pdf|IST_Hasil_dan_Norma\.xlsx|\bworkbook\b/iu', $raw) === 1) {
    $fail('Draft memuat referensi instrumen sumber yang dilarang');
}

$expectedStatus = [
    'overall_status' => 'human_review_passed',
    'review_status' => 'human_review_passed',
    'active' => false,
    'approved' => false,
    'frozen' => false,
    'imported' => false,
];

foreach ($expectedStatus as $field => $expected) {
    if (($data['status'][$field] ?? null) !== $expected) {
        $fail("Status {$field} tidak sesuai");
    }
}

foreach ([
    'automated_language_review',
    'automated_logic_review',
    'automated_association_review',
    'automated_memory_design_review',
    'automated_leakage_review',
] as $field) {
    if (($data['status'][$field] ?? null) !== 'passed') {
        $fail("Status otomatis {$field} harus passed");
    }
}

foreach ([
    'human_language_review',
    'human_logic_review',
    'human_association_review',
    'human_memory_design_review',
    'human_leakage_review',
] as $field) {
    if (($data['status'][$field] ?? null) !== 'passed') {
        $fail("Status manusia {$field} harus passed");
    }
}

$expectedSubtest = [
    'code' => 'ME',
    'answer_type' => 'single_choice',
    'memorization_seconds' => 120,
    'answering_seconds' => 240,
    'duration_seconds' => 360,
    'single_start' => true,
    'scored_question_count' => 12,
    'maximum_score' => 12,
];

foreach ($expectedSubtest as $field => $expected) {
    if (($data['subtest'][$field] ?? null) !== $expected) {
        $fail("Kontrak subtes {$field} tidak sesuai");
    }
}

$metadataFields = [
    'content_origin' => 'original_internal',
    'source_reference' => null,
    'copyright_status' => 'internally_authored',
    'normative_compatibility' => 'none',
    'review_status' => 'human_review_passed',
    'active' => false,
];

$validateMetadata = static function (array $record, string $label) use ($metadataFields, $fail): void {
    foreach ($metadataFields as $field => $expected) {
        if (! array_key_exists($field, $record) || $record[$field] !== $expected) {
            $fail("Metadata {$field} tidak sesuai pada {$label}");
        }
    }
};

$example = $data['example'] ?? null;

if (! is_array($example)) {
    $fail('Tepat satu example wajib tersedia');
    $example = [];
} else {
    $validateMetadata($example, 'me-example-001');
}

if (($example['logical_id'] ?? null) !== 'me-example-001'
    || ($example['kind'] ?? null) !== 'example'
    || ($example['display_order'] ?? null) !== 0
    || ($example['answer_type'] ?? null) !== 'single_choice') {
    $fail('Kontrak record example tidak sesuai');
}

$examplePairs = $example['pairs'] ?? [];
$exampleOptions = $example['options'] ?? [];

if (! is_array($examplePairs) || count($examplePairs) !== 5) {
    $fail('Example harus memuat tepat lima pasangan');
    $examplePairs = [];
}

$expectedExamplePairs = [
    ['cue' => 'tas', 'associate' => 'senja'],
    ['cue' => 'roti', 'associate' => 'pagar'],
    ['cue' => 'lampu', 'associate' => 'pasir'],
    ['cue' => 'meja', 'associate' => 'kabut'],
    ['cue' => 'bukit', 'associate' => 'daun'],
];

if ($examplePairs !== $expectedExamplePairs) {
    $fail('Isi atau urutan lima pasangan example tidak sesuai hasil review');
}

if (! is_array($exampleOptions) || count($exampleOptions) !== 5) {
    $fail('Example harus memuat tepat lima opsi');
    $exampleOptions = [];
}

$expectedExampleOptionTexts = ['A' => 'kabut', 'B' => 'pagar', 'C' => 'senja', 'D' => 'pasir', 'E' => 'daun'];
$actualExampleOptionTexts = [];

foreach ($exampleOptions as $option) {
    if (is_array($option) && is_string($option['code'] ?? null) && is_string($option['text'] ?? null)) {
        $actualExampleOptionTexts[$option['code']] = $option['text'];
    }
}

if ($actualExampleOptionTexts !== $expectedExampleOptionTexts) {
    $fail('Opsi example harus A kabut, B pagar, C senja, D pasir, E daun');
}

$exampleCorrect = array_values(array_filter(
    $exampleOptions,
    static fn (mixed $option): bool => is_array($option) && ($option['is_correct'] ?? null) === true,
));

if (count($exampleCorrect) !== 1 || ($exampleCorrect[0]['score_value'] ?? null) !== 1) {
    $fail('Example harus memiliki tepat satu jawaban skor 1');
}

if (($exampleCorrect[0]['code'] ?? null) !== 'B' || ($exampleCorrect[0]['text'] ?? null) !== 'pagar') {
    $fail('Key example harus tetap B/pagar');
}

foreach ($exampleOptions as $option) {
    if (! is_array($option)
        || ! in_array($option['score_value'] ?? null, [0, 1], true)
        || (($option['score_value'] ?? null) === 1) !== (($option['is_correct'] ?? null) === true)) {
        $fail('Opsi example tidak memenuhi scoring binary');
    }
}

$pairs = $data['main_pairs'] ?? null;

if (! is_array($pairs) || count($pairs) !== 15) {
    $fail('Materi utama harus memuat tepat 15 pasangan');
    $pairs = [];
}

$pairById = [];
$allMainWords = [];
$pairOrders = [];
$testedPairIds = [];
$fillerPairIds = [];

foreach ($pairs as $index => $pair) {
    if (! is_array($pair)) {
        $fail('Pair record harus object');
        continue;
    }

    $expectedId = sprintf('me-pair-%03d', $index + 1);
    $id = $pair['logical_id'] ?? null;
    $cue = $pair['cue'] ?? null;
    $associate = $pair['associate'] ?? null;
    $order = $pair['display_order'] ?? null;

    if ($id !== $expectedId) {
        $fail("Logical ID pair urutan ".($index + 1).' tidak sesuai');
    }

    if (! is_string($cue) || $cue === '' || ! is_string($associate) || $associate === '') {
        $fail("Cue/associate tidak valid pada {$expectedId}");
        continue;
    }

    if (! is_int($order) || $order !== $index + 1 || isset($pairOrders[$order])) {
        $fail("Display order pair tidak valid pada {$expectedId}");
    }

    $pairOrders[$order] = true;
    $pairById[(string) $id] = $pair;
    $validateMetadata($pair, $expectedId);

    foreach ([$cue, $associate] as $word) {
        $normalized = strtolower(trim($word));

        if (isset($allMainWords[$normalized])) {
            $fail("Kata materi utama berulang: {$word}");
        }

        $allMainWords[$normalized] = $expectedId;
    }

    $natural = $pair['association_review']['natural_association'] ?? null;
    $semantic = $pair['association_review']['semantic_relation'] ?? null;
    $phonological = $pair['phonological_review']['similarity'] ?? null;
    $outlier = $pair['memorability_review']['outlier'] ?? null;

    if (! in_array($natural, ['none', 'weak'], true)
        || ! in_array($semantic, ['none', 'weak'], true)
        || ! in_array($phonological, ['none', 'weak'], true)
        || $outlier !== false
        || ($pair['semantic_review'] ?? null) !== 'pass') {
        $fail("Audit asosiasi pair tidak lulus pada {$expectedId}");
    }

    if (($pair['tested'] ?? null) === true) {
        $testedPairIds[] = $expectedId;

        if (! is_string($pair['question_logical_id'] ?? null)) {
            $fail("Tested pair tidak mempunyai question_logical_id: {$expectedId}");
        }
    } elseif (($pair['tested'] ?? null) === false) {
        $fillerPairIds[] = $expectedId;

        if (! array_key_exists('question_logical_id', $pair) || $pair['question_logical_id'] !== null) {
            $fail("Filler pair mempunyai question_logical_id: {$expectedId}");
        }
    } else {
        $fail("Status tested pair tidak boolean: {$expectedId}");
    }
}

if (count($testedPairIds) !== 12 || count($fillerPairIds) !== 3) {
    $fail('Distribusi pair harus tepat 12 tested dan 3 filler');
}

$exampleWords = [];
$exampleAssociates = [];
foreach ($examplePairs as $pair) {
    foreach (['cue', 'associate'] as $side) {
        if (! is_string($pair[$side] ?? null)) {
            $fail('Kata example tidak valid');
            continue;
        }

        $word = strtolower(trim($pair[$side]));
        if (isset($allMainWords[$word])) {
            $fail("Example memakai kata materi utama: {$word}");
        }
        if (isset($exampleWords[$word])) {
            $fail("Kata example berulang: {$word}");
        }
        $exampleWords[$word] = true;
    }

    if (is_string($pair['associate'] ?? null)) {
        $exampleAssociates[] = strtolower(trim($pair['associate']));
    }
}

foreach ($exampleOptions as $option) {
    $word = strtolower(trim((string) ($option['text'] ?? '')));
    if ($word !== '' && isset($allMainWords[$word])) {
        $fail("Opsi example memakai kata materi utama: {$word}");
    }

    if ($word === '' || ! in_array($word, $exampleAssociates, true)) {
        $fail("Opsi example tidak berasal dari sisi associate materi contoh: {$word}");
    }
}

$questions = $data['questions'] ?? null;

if (! is_array($questions) || count($questions) !== 12) {
    $fail('Harus tersedia tepat 12 scored questions');
    $questions = [];
}

$difficultyCounts = array_fill_keys(['easy', 'medium', 'hard'], 0);
$directionCounts = array_fill_keys(['cue_to_associate', 'associate_to_cue'], 0);
$difficultyDirectionCounts = [
    'easy' => array_fill_keys(['cue_to_associate', 'associate_to_cue'], 0),
    'medium' => array_fill_keys(['cue_to_associate', 'associate_to_cue'], 0),
    'hard' => array_fill_keys(['cue_to_associate', 'associate_to_cue'], 0),
];
$keyCounts = array_fill_keys(['A', 'B', 'C', 'D', 'E'], 0);
$correctKeys = [];
$distractorFrequency = [];
$targetFrequency = [];
$questionIds = [];

foreach ($questions as $index => $question) {
    if (! is_array($question)) {
        $fail('Question record harus object');
        continue;
    }

    $expectedId = sprintf('me-%03d', $index + 1);
    $id = $question['logical_id'] ?? null;
    $direction = $question['recall_direction'] ?? null;
    $difficulty = $question['difficulty_target'] ?? null;
    $targetId = $question['target_pair_id'] ?? null;
    $options = $question['options'] ?? null;

    if ($id !== $expectedId
        || ($question['subtest_code'] ?? null) !== 'ME'
        || ($question['kind'] ?? null) !== 'scored'
        || ($question['display_order'] ?? null) !== $index + 1
        || ($question['answer_type'] ?? null) !== 'single_choice') {
        $fail("Kontrak question tidak sesuai pada {$expectedId}");
    }

    $questionIds[] = (string) $id;
    $validateMetadata($question, $expectedId);

    if (! array_key_exists((string) $difficulty, $difficultyCounts)) {
        $fail("Difficulty tidak valid pada {$expectedId}");
    } else {
        $difficultyCounts[$difficulty]++;
    }

    if (! array_key_exists((string) $direction, $directionCounts)) {
        $fail("Recall direction tidak valid pada {$expectedId}");
        continue;
    }
    $directionCounts[$direction]++;

    if (isset($difficultyDirectionCounts[(string) $difficulty][(string) $direction])) {
        $difficultyDirectionCounts[$difficulty][$direction]++;
    }

    if (! is_string($targetId) || ! isset($pairById[$targetId]) || ($pairById[$targetId]['tested'] ?? null) !== true) {
        $fail("Target pair tidak valid pada {$expectedId}");
        continue;
    }

    $targetFrequency[$targetId] = ($targetFrequency[$targetId] ?? 0) + 1;

    if (($pairById[$targetId]['question_logical_id'] ?? null) !== $id) {
        $fail("Relasi pair-question tidak konsisten pada {$expectedId}");
    }

    if (! is_array($options) || count($options) !== 5) {
        $fail("Question {$expectedId} harus mempunyai lima opsi");
        continue;
    }

    $codes = [];
    $texts = [];
    $correctOptions = [];
    $answerSide = $direction === 'cue_to_associate' ? 'associate' : 'cue';

    foreach ($options as $optionIndex => $option) {
        $expectedCode = ['A', 'B', 'C', 'D', 'E'][$optionIndex];
        $code = $option['code'] ?? null;
        $text = $option['text'] ?? null;
        $sourcePairId = $option['source_pair_id'] ?? null;
        $isCorrect = $option['is_correct'] ?? null;
        $score = $option['score_value'] ?? null;

        if ($code !== $expectedCode || isset($codes[(string) $code])) {
            $fail("Kode opsi tidak valid pada {$expectedId}");
        }
        $codes[(string) $code] = true;

        if (! is_string($text) || $text === '' || isset($texts[strtolower((string) $text)])) {
            $fail("Teks opsi tidak valid/duplikat pada {$expectedId}");
        }
        $texts[strtolower((string) $text)] = true;

        if (! is_string($sourcePairId) || ! isset($pairById[$sourcePairId])) {
            $fail("source_pair_id opsi tidak valid pada {$expectedId}");
            continue;
        }

        if ($text !== $pairById[$sourcePairId][$answerSide]) {
            $fail("Opsi tidak berasal dari sisi {$answerSide} pada {$expectedId}");
        }

        if (! in_array($score, [0, 1], true) || (($score === 1) !== ($isCorrect === true))) {
            $fail("Scoring binary tidak konsisten pada {$expectedId} opsi {$code}");
        }

        if ($isCorrect === true) {
            $correctOptions[] = $option;
        } else {
            $distractorFrequency[$text] = ($distractorFrequency[$text] ?? 0) + 1;
        }
    }

    if (count($correctOptions) !== 1) {
        $fail("Question {$expectedId} tidak mempunyai tepat satu jawaban benar");
        continue;
    }

    $correct = $correctOptions[0];
    if (($correct['source_pair_id'] ?? null) !== $targetId
        || ($correct['text'] ?? null) !== $pairById[$targetId][$answerSide]) {
        $fail("Jawaban benar tidak cocok dengan target pair pada {$expectedId}");
    }

    $correctKey = (string) ($correct['code'] ?? '');
    if (isset($keyCounts[$correctKey])) {
        $keyCounts[$correctKey]++;
        $correctKeys[] = $correctKey;
    } else {
        $fail("Correct key tidak valid pada {$expectedId}");
    }

    foreach (['ambiguity_review', 'leakage_review', 'language_review_notes', 'logic_review_notes'] as $reviewField) {
        if (! is_string($question[$reviewField] ?? null) || $question[$reviewField] === '') {
            $fail("Review {$reviewField} kosong pada {$expectedId}");
        }
    }

    if (! is_string($question['difficulty_rationale'] ?? null) || trim($question['difficulty_rationale']) === '') {
        $fail("Difficulty rationale kosong pada {$expectedId}");
    }

    if (preg_match('/reverse recall (otomatis|selalu) lebih sulit/iu', (string) ($question['difficulty_rationale'] ?? '')) === 1) {
        $fail("Difficulty rationale menggantungkan kesulitan pada reverse recall pada {$expectedId}");
    }
}

if ($difficultyCounts !== ['easy' => 4, 'medium' => 5, 'hard' => 3]) {
    $fail('Distribusi difficulty harus 4 easy, 5 medium, 3 hard');
}

if ($directionCounts !== ['cue_to_associate' => 6, 'associate_to_cue' => 6]) {
    $fail('Distribusi recall direction harus 6 forward dan 6 reverse');
}

$expectedDifficultyDirections = [
    'easy' => ['cue_to_associate' => 2, 'associate_to_cue' => 2],
    'medium' => ['cue_to_associate' => 3, 'associate_to_cue' => 2],
    'hard' => ['cue_to_associate' => 1, 'associate_to_cue' => 2],
];

if ($difficultyDirectionCounts !== $expectedDifficultyDirections) {
    $fail('Distribusi direction per difficulty harus easy 2/2, medium 3/2, hard 1/2');
}

if ($keyCounts !== ['A' => 2, 'B' => 2, 'C' => 3, 'D' => 3, 'E' => 2]) {
    $fail('Distribusi key harus A2 B2 C3 D3 E2');
}

if (count($targetFrequency) !== 12 || array_filter($targetFrequency, static fn (int $count): bool => $count !== 1) !== []) {
    $fail('Setiap tested pair harus menjadi target tepat satu question');
}

foreach ($testedPairIds as $pairId) {
    if (($targetFrequency[$pairId] ?? 0) !== 1) {
        $fail("Tested pair tidak ditanyakan tepat satu kali: {$pairId}");
    }
}

foreach ($fillerPairIds as $pairId) {
    if (isset($targetFrequency[$pairId])) {
        $fail("Filler pair menjadi target question: {$pairId}");
    }
}

foreach ($distractorFrequency as $word => $frequency) {
    if ($frequency > 4) {
        $fail("Distraktor {$word} digunakan lebih dari empat kali");
    }
}

$allIds = array_merge(
    [(string) ($example['logical_id'] ?? '')],
    array_keys($pairById),
    $questionIds,
);

if (count($allIds) !== count(array_unique($allIds))) {
    $fail('Logical ID harus unik untuk example, pair, dan question');
}

$participant = $data['participant_facing'] ?? null;

if (! is_array($participant)) {
    $fail('Participant-facing block wajib tersedia');
    $participant = [];
}

$forbiddenParticipantKeys = [
    'is_correct',
    'score_value',
    'rationale_internal',
    'target_pair_id',
    'source_pair_id',
    'question_logical_id',
    'tested',
    'key',
    'correct_option_key',
    'filler',
];

$inspectParticipant = static function (mixed $value, string $path = 'participant_facing') use (&$inspectParticipant, $forbiddenParticipantKeys, $fail): void {
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            if (is_string($key) && in_array(strtolower($key), $forbiddenParticipantKeys, true)) {
                $fail("Field sensitif {$key} ditemukan pada {$path}");
            }

            $inspectParticipant($child, $path.'.'.$key);
        }

        return;
    }

    if (is_string($value) && preg_match('/\bDEV\b/u', $value) === 1) {
        $fail("Marker DEV ditemukan pada {$path}");
    }
};

$inspectParticipant($participant);

if (($participant['example']['pairs'] ?? null) !== $expectedExamplePairs) {
    $fail('Participant-facing example harus memuat lima pasangan hasil review');
}

$participantExampleOptions = [];
foreach ($participant['example']['question']['options'] ?? [] as $option) {
    if (is_array($option) && is_string($option['code'] ?? null) && is_string($option['text'] ?? null)) {
        $participantExampleOptions[$option['code']] = $option['text'];
    }
}

if ($participantExampleOptions !== $expectedExampleOptionTexts) {
    $fail('Participant-facing opsi example tidak sesuai hasil review');
}

if (count($participant['memorization']['pairs'] ?? []) !== 15) {
    $fail('Participant memorization block harus memuat 15 pasangan');
}

if (count($participant['answering']['questions'] ?? []) !== 12) {
    $fail('Participant answering block harus memuat 12 question aman');
}

if (array_key_exists('pairs', $participant['answering'] ?? [])) {
    $fail('Participant answering block tidak boleh memuat daftar pasangan');
}

if ($errors !== []) {
    fwrite(STDERR, "Validasi draft Tahap 16 gagal:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
    exit(1);
}

ksort($distractorFrequency);

echo "Validasi draft Tahap 16 lulus.\n";
echo 'Example/scored: 1/12; example pairs=5'.PHP_EOL;
echo 'Main/tested/filler pairs: 15/12/3'.PHP_EOL;
echo 'Recall direction: cue_to_associate=6, associate_to_cue=6'.PHP_EOL;
echo 'Difficulty: easy=4, medium=5, hard=3'.PHP_EOL;
echo 'Difficulty direction: easy=2/2, medium=3/2, hard=1/2 (forward/reverse)'.PHP_EOL;
echo 'Correct keys: '.implode(',', $correctKeys).PHP_EOL;
echo 'Key distribution: A=2, B=2, C=3, D=3, E=2'.PHP_EOL;
echo 'Maximum distractor frequency: '.max($distractorFrequency).PHP_EOL;
echo 'Status: human_review_passed; active=false; approved=false; frozen=false; imported=false'.PHP_EOL;
