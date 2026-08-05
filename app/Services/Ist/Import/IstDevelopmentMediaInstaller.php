<?php

namespace App\Services\Ist\Import;

use App\Data\Ist\IstDevelopmentMediaStage;
use App\Exceptions\Ist\IstDevelopmentMediaException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IstDevelopmentMediaInstaller
{
    public function stage(array $validatedDataset): IstDevelopmentMediaStage
    {
        $diskName = (string) config('ist.development_media_disk', 'public');
        $disk = Storage::disk($diskName);
        $root = $this->diskRoot($disk);
        $finalDirectory = 'ist-development/v1';
        $files = $validatedDataset['media'];

        if (is_dir($root.DIRECTORY_SEPARATOR.$finalDirectory)) {
            $this->assertFinalFilesMatch($root, $finalDirectory, $files);

            return new IstDevelopmentMediaStage($diskName, '', $finalDirectory, $files, true);
        }

        $stagingDirectory = '.ist-development-stage-'.bin2hex(random_bytes(12));
        $stagingPath = $root.DIRECTORY_SEPARATOR.$stagingDirectory;
        if (! mkdir($stagingPath, 0755, true) && ! is_dir($stagingPath)) {
            throw IstDevelopmentMediaException::because('staging directory tidak dapat dibuat');
        }

        try {
            foreach ($files as $media) {
                $source = $validatedDataset['directory'].DIRECTORY_SEPARATOR.$media['file'];
                $basename = basename($media['target_path']);
                $destination = $stagingPath.DIRECTORY_SEPARATOR.$basename;
                if (! copy($source, $destination)
                    || ! hash_equals($media['sha256'], hash_file('sha256', $destination))) {
                    throw IstDevelopmentMediaException::because("checksum staging {$basename} tidak cocok");
                }
            }
        } catch (Throwable $exception) {
            $this->removeDirectory($stagingPath);
            throw $exception;
        }

        return new IstDevelopmentMediaStage($diskName, $stagingDirectory, $finalDirectory, $files);
    }

    public function publish(IstDevelopmentMediaStage $stage): void
    {
        if ($stage->alreadyPublished) {
            return;
        }

        $disk = Storage::disk($stage->disk);
        $root = $this->diskRoot($disk);
        $stagingPath = $root.DIRECTORY_SEPARATOR.$stage->stagingDirectory;
        $finalPath = $root.DIRECTORY_SEPARATOR.$stage->finalDirectory;

        if (! is_dir($stagingPath)) {
            throw IstDevelopmentMediaException::because('staging directory hilang sebelum publish');
        }

        $parent = dirname($finalPath);
        if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
            throw IstDevelopmentMediaException::because('parent directory final tidak dapat dibuat');
        }

        if (is_dir($finalPath)) {
            $this->assertFinalFilesMatch($root, $stage->finalDirectory, $stage->files);
            $this->removeDirectory($stagingPath);

            return;
        }

        if (! rename($stagingPath, $finalPath)) {
            throw IstDevelopmentMediaException::because('publish atomik staging ke final gagal');
        }

        $this->assertFinalFilesMatch($root, $stage->finalDirectory, $stage->files);
    }

    public function discard(IstDevelopmentMediaStage $stage): void
    {
        if ($stage->alreadyPublished || $stage->stagingDirectory === '') {
            return;
        }

        $root = $this->diskRoot(Storage::disk($stage->disk));
        $this->removeDirectory($root.DIRECTORY_SEPARATOR.$stage->stagingDirectory);
    }

    private function assertFinalFilesMatch(string $root, string $directory, array $files): void
    {
        $finalPath = $root.DIRECTORY_SEPARATOR.$directory;
        $expected = [];
        foreach ($files as $media) {
            $basename = basename($media['target_path']);
            $expected[$basename] = $media['sha256'];
        }
        $actual = array_values(array_filter(scandir($finalPath) ?: [], fn (string $file): bool => ! in_array($file, ['.', '..'], true)));
        sort($actual);
        $expectedNames = array_keys($expected);
        sort($expectedNames);
        if ($actual !== $expectedNames) {
            throw IstDevelopmentMediaException::because('directory final berisi file asing atau tidak lengkap');
        }
        foreach ($expected as $basename => $checksum) {
            $path = $finalPath.DIRECTORY_SEPARATOR.$basename;
            if (! is_file($path) || ! hash_equals($checksum, hash_file('sha256', $path))) {
                throw IstDevelopmentMediaException::because("file final {$basename} berbeda; tidak ditimpa");
            }
        }
    }

    private function diskRoot(FilesystemAdapter $disk): string
    {
        try {
            return rtrim($disk->path(''), DIRECTORY_SEPARATOR);
        } catch (Throwable) {
            throw IstDevelopmentMediaException::because('disk media harus mendukung filesystem lokal');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }
}
