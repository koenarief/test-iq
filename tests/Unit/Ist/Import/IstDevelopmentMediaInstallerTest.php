<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\IstDevelopmentMediaException;
use App\Services\Ist\Import\IstDevelopmentMediaInstaller;
use App\Services\Ist\Import\IstQuestionDatasetValidator;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class IstDevelopmentMediaInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('ist-development-test');
        config()->set('ist.development_media_disk', 'ist-development-test');
    }

    public function test_media_is_staged_verified_and_atomically_published(): void
    {
        $dataset = app(IstQuestionDatasetValidator::class)->validate();
        $installer = new IstDevelopmentMediaInstaller;
        $stage = $installer->stage($dataset);

        $this->assertFalse($stage->alreadyPublished);
        Storage::disk('ist-development-test')->assertExists($stage->stagingDirectory.'/dev-prompt.svg');

        $installer->publish($stage);
        Storage::disk('ist-development-test')->assertExists('ist-development/v1/dev-prompt.svg');
        Storage::disk('ist-development-test')->assertMissing($stage->stagingDirectory.'/dev-prompt.svg');
    }

    public function test_same_final_media_is_idempotent(): void
    {
        $dataset = app(IstQuestionDatasetValidator::class)->validate();
        $installer = new IstDevelopmentMediaInstaller;
        $first = $installer->stage($dataset);
        $installer->publish($first);
        $second = $installer->stage($dataset);

        $this->assertTrue($second->alreadyPublished);
        $installer->publish($second);
    }

    public function test_different_existing_final_media_is_rejected_without_overwrite(): void
    {
        Storage::disk('ist-development-test')->put('ist-development/v1/dev-prompt.svg', 'different');
        $dataset = app(IstQuestionDatasetValidator::class)->validate();

        $this->expectException(IstDevelopmentMediaException::class);
        (new IstDevelopmentMediaInstaller)->stage($dataset);
    }
}
