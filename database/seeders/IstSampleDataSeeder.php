<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\IstTestSession;
use App\Models\IstUserResponse;
use App\Models\IstAnswerKey;
use App\Services\Ist\IstScoringService;
use Illuminate\Support\Facades\Hash;

class IstSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Sample User / Peserta Tes
        $user = User::firstOrCreate(
            ['email' => 'peserta.ist@example.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Buat Sample Sesi Tes IST
        $session = IstTestSession::create([
            'age' => 24, // Usia untuk lookup kelompok norma (21 - 24 tahun)
	    'participant_number' => 8,
	    'name' => 'gs',
	    'birth_date' => '2010-01-01',
	    'test_date' => '2026-08-20',
        ]);

        // 3. Ambil Kunci Jawaban untuk Mengisi Sample Jawaban Peserta
        $answerKeys = IstAnswerKey::all();

        foreach ($answerKeys as $key) {
            // Generasi jawaban dummy: 80% kemungkinan menjawab benar
            $isCorrect = (rand(1, 100) <= 80);
            $userAnswer = $key->correct_answer;

            if (!$isCorrect) {
                if ($key->subtest === 'GE') {
                    $userAnswer = 'jawaban_salah_sample';
                } else {
                    $userAnswer = $this->getWrongAnswerChoice($key->correct_answer);
                }
            } else {
                if ($key->subtest === 'GE') {
                    // Ambil sampel kata kunci 2 poin untuk GE
                    $keywords = json_decode($key->correct_answer, true);
                    $userAnswer = $keywords['score_2'][0] ?? 'pakaian';
                }
            }

            IstUserResponse::create([
		'test_session_id' => 1,
                'subtest' => $key->subtest,
                'question_number' => $key->question_number,
                'user_answer' => $userAnswer,
            ]);
        }

        // 4. Hitung Skor Otomatis Menggunakan IstScoringService
        $scoringService = app(IstScoringService::class);
        $hasil = $scoringService->calculateSessionScore($session->id);
	// Ambil IQ Score dan Kategori dari array $hasil
	$iqScore = $hasil['iq_score'] ?? '-';
	$iqCategory = $hasil['iq_category'] ?? '-';

	$this->command->info("Sample user & sesi tes IST berhasil dibuat untuk: {$user->name} (Email: {$user->email}) | IQ: {$iqScore} ({$iqCategory})");

    }

    private function getWrongAnswerChoice(string $correct): string
    {
        $options = ['a', 'b', 'c', 'd', 'e'];
        $filtered = array_diff($options, [strtolower($correct)]);
        return $filtered[array_rand($filtered)];
    }
}
