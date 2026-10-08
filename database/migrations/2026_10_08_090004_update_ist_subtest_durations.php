<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * code => [duration_seconds, memorization_seconds, answering_seconds]
     */
    private const NEW_DURATIONS = [
        'SE' => [180, 0, 180],
        'WA' => [180, 0, 180],
        'AN' => [180, 0, 180],
        'GE' => [240, 0, 240],
        'RA' => [300, 0, 300],
        'ZR' => [300, 0, 300],
        'FA' => [180, 0, 180],
        'WU' => [300, 0, 300],
        'ME' => [240, 80, 160],
    ];

    private const PREVIOUS_DURATIONS = [
        'SE' => [360, 0, 360],
        'WA' => [360, 0, 360],
        'AN' => [420, 0, 420],
        'GE' => [480, 0, 480],
        'RA' => [600, 0, 600],
        'ZR' => [600, 0, 600],
        'FA' => [420, 0, 420],
        'WU' => [540, 0, 540],
        'ME' => [540, 180, 360],
    ];

    public function up(): void
    {
        $this->apply(self::NEW_DURATIONS);
    }

    public function down(): void
    {
        $this->apply(self::PREVIOUS_DURATIONS);
    }

    private function apply(array $durations): void
    {
        foreach ($durations as $code => [$duration, $memorization, $answering]) {
            DB::table('ist_subtests')
                ->where('code', $code)
                ->where('is_active', true)
                ->update([
                    'duration_seconds' => $duration,
                    'memorization_seconds' => $memorization,
                    'answering_seconds' => $answering,
                    'updated_at' => now(),
                ]);
        }
    }
};
