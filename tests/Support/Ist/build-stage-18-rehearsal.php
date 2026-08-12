<?php

declare(strict_types=1);
use App\Support\Ist\IstSubtestCatalog;

$projectRoot = dirname(__DIR__, 3);
require $projectRoot.'/vendor/autoload.php';
$source = $argv[1] ?? $projectRoot.'/database/data/ist-final-staging';
$target = $argv[2] ?? null;

if (! is_string($target) || trim($target) === '') {
    fwrite(STDERR, "Usage: php tests/Support/Ist/build-stage-18-rehearsal.php <source> <target>\n");
    exit(1);
}

$source = realpath($source);

if ($source === false || ! is_dir($source)) {
    fwrite(STDERR, "Source staging directory is missing.\n");
    exit(1);
}

if (file_exists($target)) {
    fwrite(STDERR, "Target already exists; refusing to overwrite it.\n");
    exit(1);
}

$targetParent = dirname($target);

if (! is_dir($targetParent) || ! is_writable($targetParent)) {
    fwrite(STDERR, "Target parent is not writable.\n");
    exit(1);
}

$readJson = static function (string $path): array {
    return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
};
$writeJson = static function (string $path, array $payload): void {
    file_put_contents(
        $path,
        json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        )."\n",
    );
};
$replaceReviewStatus = static function (mixed $value) use (&$replaceReviewStatus): mixed {
    if (! is_array($value)) {
        return $value;
    }

    foreach ($value as $key => $item) {
        if ($key === 'review_status' && $item === 'human_review_passed') {
            $value[$key] = 'approved';
        } else {
            $value[$key] = $replaceReviewStatus($item);
        }
    }

    return $value;
};

mkdir($target, 0750, true);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);

foreach ($iterator as $entry) {
    $relative = substr($entry->getPathname(), strlen($source) + 1);
    $destination = $target.'/'.$relative;

    if ($entry->isDir()) {
        if (! is_dir($destination)) {
            mkdir($destination, 0750, true);
        }
    } else {
        copy($entry->getPathname(), $destination);
    }
}

$manifestPath = $target.'/manifest.json';
$manifest = $readJson($manifestPath);
$originalFingerprint = $manifest['content_fingerprint'] ?? null;
$freezeAt = '2026-08-06T12:10:00+07:00';

$manifest['dataset_type'] = 'final';
$manifest['status'] = 'frozen';
$manifest['review_status'] = 'approved';
$manifest['approved'] = true;
$manifest['frozen'] = true;
$manifest['active'] = false;
$manifest['imported'] = false;
$manifest['instrument_version'] = '1.0.0-rehearsal';
$manifest['media_version'] = '1.0.0-rehearsal';
$manifest['approval']['completed'] = true;
$manifest['freeze'] = [
    'frozen' => true,
    'frozen_at' => $freezeAt,
    'frozen_by' => 'stage-18-clone-rehearsal',
    'freeze_version' => $manifest['question_bank_version'],
];
$manifest['frozen_at'] = $freezeAt;
$manifest['provenance']['usage_scope'] = 'clone-only-stage-18-rehearsal';
$manifest['notes'] = 'Clone-only Stage 18 rehearsal artifact; never approved for production activation.';
$catalog = array_column(IstSubtestCatalog::all(), null, 'code');

foreach ($manifest['subtests'] as &$entry) {
    $definition = $catalog[$entry['code']];
    $entry['name'] = $definition['name'];
    $entry['sequence'] = $definition['sequence'];
    $entry['question_count'] = $definition['question_count'];
    $entry['default_answer_type'] = $definition['default_answer_type'];
}
unset($entry);

$writeJson($manifestPath, $manifest);

$approvals = [
    'schema_version' => 1,
    'instrument_identifier' => $manifest['instrument_identifier'],
    'question_bank_version' => $manifest['question_bank_version'],
    'content_author' => 'internal-assessment-content-team',
    'language_reviewer' => 'stage-18-reviewed-content-checkpoint',
    'logic_reviewer' => 'stage-18-reviewed-content-checkpoint',
    'owner_approver' => 'stage-18-clone-rehearsal-owner',
    'visual_reviewer' => 'stage-18-reviewed-visual-checkpoint',
    'approved_at' => '2026-08-06T12:08:00+07:00',
    'visual_approved_at' => '2026-08-06T12:09:00+07:00',
    'decision' => 'approved',
    'approval_version' => $manifest['question_bank_version'],
    'notes' => 'Approval metadata is valid only for clone rehearsal and is not a production approval.',
];
$writeJson($target.'/approvals.json', $approvals);

foreach ($manifest['subtests'] as $entry) {
    $path = $target.'/'.$entry['file'];
    $writeJson($path, $replaceReviewStatus($readJson($path)));
}

$mediaMetadataPath = $target.'/'.$manifest['files']['media_metadata'];
$writeJson($mediaMetadataPath, $replaceReviewStatus($readJson($mediaMetadataPath)));

$files = [];
$checksumPath = $manifest['files']['checksums'];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
);

foreach ($iterator as $entry) {
    if (! $entry->isFile()) {
        continue;
    }

    $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($target) + 1));

    if ($relative !== $checksumPath) {
        $files[$relative] = hash_file('sha256', $entry->getPathname());
    }
}

ksort($files, SORT_STRING);
$writeJson($target.'/'.$checksumPath, [
    'algorithm' => 'sha256',
    'generated_at' => '2026-08-06T12:11:00+07:00',
    'files' => $files,
]);

$finalManifest = $readJson($manifestPath);

if (($finalManifest['content_fingerprint'] ?? null) !== $originalFingerprint) {
    fwrite(STDERR, "Substantive content fingerprint changed unexpectedly.\n");
    exit(1);
}

fwrite(STDOUT, "REHEARSAL_ARTIFACT={$target}\n");
fwrite(STDOUT, "CONTENT_FINGERPRINT={$originalFingerprint}\n");
fwrite(STDOUT, "ACTIVE=false\n");
