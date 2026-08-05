<?php

namespace App\Services\Ist\Import\Final;

use App\Data\Ist\IstFinalDatasetValidationResult;
use App\Data\Ist\IstFinalMediaStage;
use App\Exceptions\Ist\IstFinalMediaException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IstFinalMediaInstaller
{
    public function stage(IstFinalDatasetValidationResult $dataset): IstFinalMediaStage
    {
        $diskName = (string) config('ist.final_media_disk', 'public');
        $disk = Storage::disk($diskName);
        $root = $this->diskRoot($disk);
        $finalDirectory = 'ist/final/'.$dataset->manifest['question_bank_version'];
        $finalPath = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $finalDirectory);

        if (file_exists($finalPath)) {
            throw IstFinalMediaException::because('target version media sudah ada; reimport ditolak');
        }

        $stagingDirectory = '.ist-final-stage-'.bin2hex(random_bytes(12));
        $stagingPath = $root.DIRECTORY_SEPARATOR.$stagingDirectory;

        if (! mkdir($stagingPath, 0750, true) && ! is_dir($stagingPath)) {
            throw IstFinalMediaException::because('staging directory tidak dapat dibuat');
        }

        try {
            foreach ($dataset->media as $media) {
                $relativeTarget = substr($media['relative_path'], strlen('media/'));
                $destination = $stagingPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeTarget);
                $parent = dirname($destination);

                if (! is_dir($parent) && ! mkdir($parent, 0750, true) && ! is_dir($parent)) {
                    throw IstFinalMediaException::because('directory staging media tidak dapat dibuat');
                }

                if (! copy($media['source_path'], $destination)
                    || ! hash_equals($media['sha256'], hash_file('sha256', $destination))) {
                    throw IstFinalMediaException::because('checksum staging media tidak cocok');
                }
            }
        } catch (Throwable $exception) {
            $this->removeDirectory($stagingPath);
            throw $exception;
        }

        return new IstFinalMediaStage(
            disk: $diskName,
            stagingDirectory: $stagingDirectory,
            finalDirectory: $finalDirectory,
            files: array_values($dataset->media),
        );
    }

    public function publish(IstFinalMediaStage $stage): void
    {
        $root = $this->diskRoot(Storage::disk($stage->disk));
        $stagingPath = $root.DIRECTORY_SEPARATOR.$stage->stagingDirectory;
        $finalPath = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $stage->finalDirectory);

        if (! is_dir($stagingPath)) {
            throw IstFinalMediaException::because('staging directory hilang sebelum publish');
        }

        if (file_exists($finalPath)) {
            throw IstFinalMediaException::because('target version media muncul sebelum publish');
        }

        $parent = dirname($finalPath);

        if (! is_dir($parent) && ! mkdir($parent, 0750, true) && ! is_dir($parent)) {
            throw IstFinalMediaException::because('parent directory media final tidak dapat dibuat');
        }

        if (! rename($stagingPath, $finalPath)) {
            throw IstFinalMediaException::because('publish atomik staging media gagal');
        }

        try {
            $this->assertPublishedFiles($root, $stage);
        } catch (Throwable $exception) {
            $this->removeDirectory($finalPath);

            throw $exception;
        }
    }

    public function discard(IstFinalMediaStage $stage): void
    {
        $root = $this->diskRoot(Storage::disk($stage->disk));
        $this->removeDirectory($root.DIRECTORY_SEPARATOR.$stage->stagingDirectory);
    }

    private function assertPublishedFiles(string $root, IstFinalMediaStage $stage): void
    {
        $base = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $stage->finalDirectory);
        $expected = [];

        foreach ($stage->files as $media) {
            $relative = substr($media['relative_path'], strlen('media/'));
            $expected[$relative] = $media['sha256'];
        }

        $actual = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isLink() || ! $file->isFile()) {
                throw IstFinalMediaException::because('publish media menghasilkan entry yang tidak aman');
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            $actual[$relative] = hash_file('sha256', $file->getPathname());
        }

        ksort($actual, SORT_STRING);
        ksort($expected, SORT_STRING);

        if ($actual !== $expected) {
            throw IstFinalMediaException::because('media final tidak lengkap atau berisi file asing');
        }
    }

    private function diskRoot(FilesystemAdapter $disk): string
    {
        try {
            return rtrim($disk->path(''), DIRECTORY_SEPARATOR);
        } catch (Throwable) {
            throw IstFinalMediaException::because('disk media final harus mendukung filesystem lokal');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory) || is_link($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path) && ! is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
