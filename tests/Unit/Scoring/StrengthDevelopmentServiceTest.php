<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\StrengthDevelopmentService;
use PHPUnit\Framework\TestCase;

class StrengthDevelopmentServiceTest extends TestCase
{
    public function test_bands_match_score_md_table(): void
    {
        $service = new StrengthDevelopmentService();

        $this->assertSame('Exceptional', $service->band(90));
        $this->assertSame('Exceptional', $service->band(100));
        $this->assertSame('Strong', $service->band(89.99));
        $this->assertSame('Strong', $service->band(80));
        $this->assertSame('Adequate', $service->band(79.99));
        $this->assertSame('Adequate', $service->band(70));
        $this->assertSame('Development Area', $service->band(69.99));
        $this->assertSame('Development Area', $service->band(60));
        $this->assertSame('Weakness', $service->band(59.99));
        $this->assertSame('Weakness', $service->band(0));
    }
}
