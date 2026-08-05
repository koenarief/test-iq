<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetValidator;
use Tests\Fixtures\Ist\FinalDatasetFactory;
use Tests\TestCase;

final class IstFinalMediaValidatorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = FinalDatasetFactory::create();
    }

    protected function tearDown(): void
    {
        FinalDatasetFactory::remove($this->directory);
        parent::tearDown();
    }

    public function test_missing_media_is_rejected(): void
    {
        unlink($this->directory.'/media/options/option-a.svg');
        $this->assertRejected();
    }

    public function test_media_checksum_mismatch_is_rejected(): void
    {
        $this->mutateMetadata(function (array $data): array {
            $data['media'][0]['sha256'] = str_repeat('0', 64);

            return $data;
        });
        $this->assertRejected();
    }

    public function test_duplicate_media_logical_id_is_rejected(): void
    {
        $this->mutateMetadata(function (array $data): array {
            $data['media'][1]['logical_id'] = $data['media'][0]['logical_id'];

            return $data;
        });
        $this->assertRejected();
    }

    public function test_path_traversal_media_is_rejected(): void
    {
        $this->mutateMetadata(function (array $data): array {
            $data['media'][0]['relative_path'] = '../option-a.svg';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_answer_leaking_alt_text_is_rejected(): void
    {
        $this->mutateMetadata(function (array $data): array {
            $data['media'][0]['alt_text'] = 'Opsi benar adalah A';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_malicious_svg_is_rejected(): void
    {
        $path = $this->directory.'/media/options/option-a.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><script>alert(1)</script></svg>');
        $this->mutateMetadata(function (array $data) use ($path): array {
            $data['media'][0]['byte_size'] = filesize($path);
            $data['media'][0]['sha256'] = hash_file('sha256', $path);

            return $data;
        });
        $this->assertRejected();
    }

    private function mutateMetadata(callable $mutation): void
    {
        $path = $this->directory.'/media/metadata.json';
        FinalDatasetFactory::writeJson($path, $mutation(FinalDatasetFactory::readJson($path)));
        FinalDatasetFactory::refreshChecksums($this->directory);
    }

    private function assertRejected(): void
    {
        $this->expectException(InvalidIstFinalQuestionDatasetException::class);
        app(IstFinalQuestionDatasetValidator::class)->validate($this->directory);
    }
}
