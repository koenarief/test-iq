<?php

namespace App\Services\Ist\Import\Final;

use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use DOMDocument;
use DOMElement;
use Throwable;

final class IstFinalMediaValidator
{
    private const ALLOWED_MIME_BY_EXTENSION = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
    ];

    public function validate(
        string $directory,
        array $manifest,
        array $payload,
        string $requiredReviewStatus = 'approved',
    ): array {
        if (($payload['schema_version'] ?? null) !== ($manifest['schema_version'] ?? null)
            || ($payload['instrument_identifier'] ?? null) !== ($manifest['instrument_identifier'] ?? null)
            || ($payload['question_bank_version'] ?? null) !== ($manifest['question_bank_version'] ?? null)) {
            $this->fail('identitas media metadata tidak sesuai manifest');
        }

        $records = $payload['media'] ?? null;

        if (! is_array($records)) {
            $this->fail('media metadata harus berisi array media');
        }

        if (count($records) !== ($manifest['media']['count'] ?? null)) {
            $this->fail('jumlah media berbeda dari manifest');
        }

        $ids = [];
        $paths = [];
        $hashGroups = [];
        $validated = [];

        foreach ($records as $index => $record) {
            if (! is_array($record)) {
                $this->fail("media #{$index} harus berupa object");
            }

            $id = $record['logical_id'] ?? null;
            $relativePath = $record['relative_path'] ?? null;

            if (! is_string($id) || ! preg_match('/\A[a-z0-9][a-z0-9._-]{1,127}\z/', $id)) {
                $this->fail("logical ID media #{$index} tidak valid");
            }

            if (isset($ids[$id])) {
                $this->fail("logical ID media duplikat: {$id}");
            }

            $path = $this->normalizeRelativePath($relativePath, "media {$id}");

            if (! str_starts_with($path, 'media/') || $path === 'media/metadata.json') {
                $this->fail("path media {$id} harus berada di bawah media/");
            }

            if (isset($paths[$path])) {
                $this->fail("path media duplikat: {$path}");
            }

            $ids[$id] = true;
            $paths[$path] = true;
            $source = $directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

            if (is_link($source)) {
                $this->fail("symlink media ditolak: {$path}");
            }

            if (! is_file($source)) {
                $this->fail("file media hilang: {$path}");
            }

            $byteSize = $record['byte_size'] ?? null;
            $maximumBytes = (int) config('ist.final_media_max_bytes', 2 * 1024 * 1024);

            if (! is_int($byteSize) || $byteSize <= 0 || $byteSize > $maximumBytes
                || filesize($source) !== $byteSize) {
                $this->fail("byte size media tidak valid: {$id}");
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $declaredMime = $record['mime_type'] ?? null;
            $expectedMime = self::ALLOWED_MIME_BY_EXTENSION[$extension] ?? null;

            if ($expectedMime === null || $declaredMime !== $expectedMime) {
                $this->fail("extension dan MIME media tidak cocok: {$id}");
            }

            $actualHash = hash_file('sha256', $source);
            $declaredHash = $record['sha256'] ?? null;

            if (! is_string($declaredHash)
                || ! preg_match('/\A[a-f0-9]{64}\z/', $declaredHash)
                || ! hash_equals($declaredHash, $actualHash)) {
                $this->fail("checksum media tidak cocok: {$id}");
            }

            $alt = $record['alt_text'] ?? null;

            if (! is_string($alt) || trim($alt) === '' || $this->altLeaksAnswer($alt)) {
                $this->fail("alt text media tidak aman: {$id}");
            }

            foreach (['owner', 'provenance', 'review_status'] as $required) {
                if (! is_string($record[$required] ?? null) || trim($record[$required]) === '') {
                    $this->fail("metadata {$required} media kosong: {$id}");
                }
            }

            if ($record['review_status'] !== $requiredReviewStatus) {
                $this->fail("status review media tidak sesuai kontrak: {$id}");
            }

            [$width, $height] = $extension === 'svg'
                ? $this->validateSvg($source, $id)
                : $this->rasterDimensions($source, $id, $declaredMime);

            $maximumDimension = (int) config('ist.final_media_max_dimension', 4096);

            if (($record['width'] ?? null) !== $width
                || ($record['height'] ?? null) !== $height
                || $width < 1 || $height < 1
                || $width > $maximumDimension || $height > $maximumDimension) {
                $this->fail("dimensi media tidak valid: {$id}");
            }

            $hashGroups[$actualHash][] = $record;
            $validated[$id] = [
                ...$record,
                'relative_path' => $path,
                'source_path' => $source,
                'target_path' => 'ist/final/'.$manifest['question_bank_version'].'/'.substr($path, 6),
            ];
        }

        foreach ($hashGroups as $hash => $duplicates) {
            if (count($duplicates) < 2) {
                continue;
            }

            foreach ($duplicates as $duplicate) {
                if (! is_string($duplicate['duplicate_hash_reason'] ?? null)
                    || trim($duplicate['duplicate_hash_reason']) === '') {
                    $this->fail('duplicate media hash harus mempunyai alasan eksplisit');
                }
            }
        }

        return $validated;
    }

    public function normalizeRelativePath(mixed $value, string $label): string
    {
        if (! is_string($value) || $value === '' || $value !== strtolower($value)
            || str_contains($value, '\\') || str_contains($value, '..')
            || str_starts_with($value, '/') || str_contains($value, '//')
            || preg_match('/\A[a-z]:/i', $value) === 1
            || preg_match('/\A[a-z0-9][a-z0-9._\/-]*\z/', $value) !== 1) {
            $this->fail("path {$label} tidak aman atau tidak canonical");
        }

        return $value;
    }

    private function altLeaksAnswer(string $alt): bool
    {
        return preg_match('/\b(correct|key|benar|jawaban)\b|opsi\s+benar|pola\s+jawaban/iu', $alt) === 1;
    }

    private function rasterDimensions(string $path, string $id, string $declaredMime): array
    {
        $info = @getimagesize($path);

        if (! is_array($info) || ! isset($info[0], $info[1], $info['mime'])
            || $info['mime'] !== $declaredMime) {
            $this->fail("raster media tidak valid: {$id}");
        }

        return [(int) $info[0], (int) $info[1]];
    }

    private function validateSvg(string $path, string $id): array
    {
        $contents = file_get_contents($path);

        if (! is_string($contents)
            || stripos($contents, '<!doctype') !== false
            || stripos($contents, '<!entity') !== false
            || stripos($contents, 'javascript:') !== false
            || preg_match('/url\s*\(\s*[\'\"]?(?:https?:|\/\/|data:)/i', $contents) === 1) {
            $this->fail("SVG media berbahaya: {$id}");
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;

        try {
            $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS);
        } catch (Throwable) {
            $loaded = false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded || $document->documentElement?->localName !== 'svg') {
            $this->fail("SVG media tidak valid: {$id}");
        }

        $forbiddenElements = ['script', 'foreignObject', 'iframe', 'object', 'embed', 'audio', 'video'];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            if (in_array($element->localName, $forbiddenElements, true)) {
                $this->fail("SVG media memuat elemen berbahaya: {$id}");
            }

            for ($index = 0; $index < $element->attributes->length; $index++) {
                $attribute = $element->attributes->item($index);

                if ($attribute === null) {
                    continue;
                }

                $name = strtolower($attribute->nodeName);
                $value = trim($attribute->nodeValue ?? '');

                if (str_starts_with($name, 'on')
                    || (in_array($name, ['href', 'xlink:href', 'src'], true)
                        && $value !== '' && ! str_starts_with($value, '#'))) {
                    $this->fail("SVG media memuat referensi atau event berbahaya: {$id}");
                }
            }
        }

        $root = $document->documentElement;
        $width = $this->svgDimension($root->getAttribute('width'));
        $height = $this->svgDimension($root->getAttribute('height'));

        if ($width === null || $height === null) {
            $viewBox = preg_split('/\s+/', trim($root->getAttribute('viewBox')));

            if (count($viewBox) === 4 && is_numeric($viewBox[2]) && is_numeric($viewBox[3])) {
                $width ??= (int) round((float) $viewBox[2]);
                $height ??= (int) round((float) $viewBox[3]);
            }
        }

        if ($width === null || $height === null) {
            $this->fail("dimensi SVG tidak tersedia: {$id}");
        }

        return [$width, $height];
    }

    private function svgDimension(string $value): ?int
    {
        if (preg_match('/\A([0-9]+)(?:px)?\z/i', trim($value), $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function fail(string $reason): never
    {
        throw InvalidIstFinalQuestionDatasetException::because($reason);
    }
}
