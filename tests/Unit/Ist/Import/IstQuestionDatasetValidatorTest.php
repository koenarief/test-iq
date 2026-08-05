<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Services\Ist\Import\IstQuestionDatasetValidator;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class IstQuestionDatasetValidatorTest extends TestCase
{
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        $filesystem = new Filesystem;
        foreach ($this->temporaryDirectories as $directory) {
            $filesystem->deleteDirectory($directory);
        }
        parent::tearDown();
    }

    public function test_complete_dataset_has_exact_development_counts_and_expected_results_are_metadata_only(): void
    {
        $result = app(IstQuestionDatasetValidator::class)->validate();

        $this->assertSame([
            'subtests' => 9,
            'scored_questions' => 104,
            'example_questions' => 9,
            'options' => 435,
            'media' => 6,
            'expected_answers' => 104,
        ], $result['counts']);
        $this->assertArrayNotHasKey('expected_results', $result);
        $this->assertCount(9, $result['subtests']);
    }

    public function test_one_byte_json_change_is_rejected_by_checksum(): void
    {
        $directory = $this->copyDataset();
        file_put_contents($directory.'/se.json', file_get_contents($directory.'/se.json').' ');

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetValidator::class)->validate($directory);
    }

    public function test_one_byte_media_change_is_rejected_by_checksum(): void
    {
        $directory = $this->copyDataset();
        file_put_contents($directory.'/media/dev-prompt.svg', file_get_contents($directory.'/media/dev-prompt.svg').' ');

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetValidator::class)->validate($directory);
    }

    public function test_foreign_file_is_rejected(): void
    {
        $directory = $this->copyDataset();
        file_put_contents($directory.'/foreign.txt', 'not declared');

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetValidator::class)->validate($directory);
    }

    #[DataProvider('invalidChecksumProvider')]
    public function test_empty_or_malformed_checksum_is_rejected(string $checksum): void
    {
        $directory = $this->copyDataset();
        $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['subtests']['SE']['sha256'] = $checksum;
        file_put_contents($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetValidator::class)->validate($directory);
    }

    public static function invalidChecksumProvider(): array
    {
        return [[''], ['abc'], [str_repeat('G', 64)]];
    }

    public function test_unsafe_svg_is_rejected_even_with_updated_checksum(): void
    {
        $directory = $this->copyDataset();
        $svgPath = $directory.'/media/dev-prompt.svg';
        file_put_contents($svgPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><text>DEV</text></svg>');
        $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['media'][0]['sha256'] = hash_file('sha256', $svgPath);
        file_put_contents($directory.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetValidator::class)->validate($directory);
    }

    private function copyDataset(): string
    {
        $directory = sys_get_temp_dir().'/ist-dataset-test-'.bin2hex(random_bytes(8));
        (new Filesystem)->copyDirectory(database_path('data/ist-development'), $directory);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}
