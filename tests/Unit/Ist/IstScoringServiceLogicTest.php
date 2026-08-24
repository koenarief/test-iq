<?php

namespace Tests\Unit\Ist;

use App\Services\Ist\IstScoringService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class IstScoringServiceLogicTest extends TestCase
{
    private IstScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new IstScoringService;
    }

    private function callPrivate(string $method, array $args)
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod($method);

        return $method->invokeArgs($this->service, $args);
    }

    public function test_ge_keyword_scoring_prioritises_score_2_over_score_1(): void
    {
        $config = json_encode([
            'score_2' => ['burung'],
            'score_1' => ['terbang'],
        ]);

        $this->assertSame(2, $this->callPrivate('scoreGeQuestion', ['Seekor burung besar', $config]));
        $this->assertSame(1, $this->callPrivate('scoreGeQuestion', ['ia bisa terbang', $config]));
        $this->assertSame(0, $this->callPrivate('scoreGeQuestion', ['hewan berkaki empat', $config]));
        $this->assertSame(2, $this->callPrivate('scoreGeQuestion', ['burung yang bisa terbang', $config]));
    }

    public function test_ge_scoring_without_json_config_falls_back_to_exact_match_worth_two_points(): void
    {
        $this->assertSame(2, $this->callPrivate('scoreGeQuestion', [' Kucing ', 'kucing']));
        $this->assertSame(0, $this->callPrivate('scoreGeQuestion', ['anjing', 'kucing']));
    }

    public function test_dominance_profile_thresholds(): void
    {
        $balanced = ['SE' => 100, 'WA' => 100, 'AN' => 100, 'GE' => 100, 'FA' => 100, 'WU' => 100, 'ZR' => 100, 'RA' => 100];
        $this->assertSame('Seimbang (Balanced)', $this->callPrivate('calculateDominanceProfile', [$balanced]));

        $verbalHigh = ['SE' => 110, 'WA' => 100, 'AN' => 100, 'GE' => 100, 'FA' => 100, 'WU' => 100, 'ZR' => 100, 'RA' => 100];
        $this->assertSame('W-Dominant (Verbal High)', $this->callPrivate('calculateDominanceProfile', [$verbalHigh]));

        $spatialHigh = ['SE' => 100, 'WA' => 100, 'AN' => 100, 'GE' => 100, 'FA' => 110, 'WU' => 100, 'ZR' => 100, 'RA' => 100];
        $this->assertSame('M-Dominant (Spatial High)', $this->callPrivate('calculateDominanceProfile', [$spatialHigh]));

        $justUnderThreshold = ['SE' => 109, 'WA' => 100, 'AN' => 100, 'GE' => 100, 'FA' => 100, 'WU' => 100, 'ZR' => 100, 'RA' => 100];
        $this->assertSame('Seimbang (Balanced)', $this->callPrivate('calculateDominanceProfile', [$justUnderThreshold]));
    }

    public function test_subtest_category_boundaries(): void
    {
        $this->assertSame('Sangat Tinggi', $this->callPrivate('getSubtestCategory', [119]));
        $this->assertSame('Tinggi', $this->callPrivate('getSubtestCategory', [118]));
        $this->assertSame('Tinggi', $this->callPrivate('getSubtestCategory', [109]));
        $this->assertSame('Rata-Rata', $this->callPrivate('getSubtestCategory', [108]));
        $this->assertSame('Rata-Rata', $this->callPrivate('getSubtestCategory', [91]));
        $this->assertSame('Rendah', $this->callPrivate('getSubtestCategory', [90]));
        $this->assertSame('Rendah', $this->callPrivate('getSubtestCategory', [82]));
        $this->assertSame('Sangat Rendah', $this->callPrivate('getSubtestCategory', [81]));
    }

    public function test_calculate_dominance_thresholds(): void
    {
        $this->assertSame('Dominan Verbal / Konseptual', $this->callPrivate('calculateDominance', [120, 100]));
        $this->assertSame('Dominan Spasial / Praktis', $this->callPrivate('calculateDominance', [100, 120]));
        $this->assertSame('Seimbang (Balanced)', $this->callPrivate('calculateDominance', [115, 100]));
        $this->assertSame('Seimbang (Balanced)', $this->callPrivate('calculateDominance', [100, 100]));
    }
}
