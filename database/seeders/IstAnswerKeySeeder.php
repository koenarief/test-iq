<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IstAnswerKey;
use Illuminate\Support\Facades\DB;

class IstAnswerKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tabel sebelum seeding agar data tidak duplikat
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        IstAnswerKey::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [];

        // ------------------------------------------------------------------
        // 1. SUBTES SE (SELECTION OF INFORMATION) - Soal 1 s/d 20
        // ------------------------------------------------------------------
        $seKeys = ['e', 'c', 'd', 'd', 'e', 'b', 'c', 'a', 'e', 'b', 'c', 'd', 'd', 'e', 'c', 'a', 'b', 'b', 'c', 'a'];
        foreach ($seKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'SE',
                'question_number' => $idx + 1,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 2. SUBTES WA (WORD SELECTION) - Soal 21 s/d 40
        // ------------------------------------------------------------------
        $waKeys = ['a', 'b', 'd', 'c', 'c', 'c', 'c', 'd', 'd', 'a', 'e', 'a', 'a', 'b', 'c', 'a', 'd', 'e', 'b', 'c'];
        foreach ($waKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'WA',
                'question_number' => $idx + 21,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 3. SUBTES AN (ANALOGIES) - Soal 41 s/d 60
        // ------------------------------------------------------------------
        $anKeys = ['c', 'e', 'd', 'd', 'd', 'b', 'd', 'b', 'e', 'd', 'c', 'c', 'c', 'c', 'e', 'c', 'c', 'e', 'e', 'e'];
        foreach ($anKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'AN',
                'question_number' => $idx + 41,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 4. SUBTES GE (GEMEINSAMKEITEN / KESAMAAN KATA) - Soal 61 s/d 76
        // Catatan: Kunci berupa kata kunci JSON untuk grading skala 0, 1, 2
        // ------------------------------------------------------------------
        $geKeywords = [
            61 => ['score_2' => ['makanan', 'pangan', 'nutrisi'], 'score_1' => ['dikonsumsi', 'dimakan', 'zat']],
            62 => ['score_2' => ['pakaian', 'busana', 'sandang'], 'score_1' => ['dipakai', 'penutup tubuh']],
            63 => ['score_2' => ['alat musik', 'instrumen musik'], 'score_1' => ['suara', 'bunyi', 'dimainkan']],
            64 => ['score_2' => ['alat transportasi', 'kendaraan'], 'score_1' => ['berjalan', 'dinaiki', 'alat angkut']],
            65 => ['score_2' => ['perhiasan', 'aksesoris'], 'score_1' => ['barang berharga', 'hiasan', 'logam']],
            66 => ['score_2' => ['panca indera', 'organ indera', 'alat indera'], 'score_1' => ['organ tubuh', 'bagian kepala']],
            67 => ['score_2' => ['bunga', 'kembang'], 'score_1' => ['tanaman', 'tumbuhan', 'wangi']],
            68 => ['score_2' => ['bangunan', 'gedung', 'tempat tinggal'], 'score_1' => ['konstruksi', 'tempat berteduh']],
            69 => ['score_2' => ['senjata', 'alat perang'], 'score_1' => ['tajam', 'alat pertahanan', 'besi']],
            70 => ['score_2' => ['burung', 'aves', 'unggas'], 'score_1' => ['hewan terbang', 'binatang']],
            71 => ['score_2' => ['perabot', 'mebel', 'furniture'], 'score_1' => ['alat rumah tangga', 'kayu']],
            72 => ['score_2' => ['pembagi', 'skala', 'ukuran'], 'score_1' => ['alat ukur', 'angka']],
            73 => ['score_2' => ['arah', 'mata angin', 'orientasi'], 'score_1' => ['petunjuk', 'posisi']],
            74 => ['score_2' => ['iklim', 'cuaca', 'kondisi alam'], 'score_1' => ['suasana udara', 'panas dingin']],
            75 => ['score_2' => ['perasaan', 'emosi', 'afeksi'], 'score_1' => ['kondisi jiwa', 'hati']],
            76 => ['score_2' => ['satuan waktu', 'durasi', 'ukuran waktu'], 'score_1' => ['waktu', 'perjalanan masa']]
        ];

        foreach ($geKeywords as $qNum => $keywords) {
            $data[] = [
                'subtest' => 'GE',
                'question_number' => $qNum,
                'correct_answer' => json_encode($keywords),
                'score_weight' => 2, // Skor maksimal per nomor GE adalah 2
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 5. SUBTES RA (ARITHMETICAL PROBLEMS) - Soal 77 s/d 96
        // ------------------------------------------------------------------
        $raKeys = ['35', '280', '250', '26', '30', '70', '45', '50', '48', '78', '19', '6', '57', '90', '120', '17', '24', '5', '48', '9'];
        foreach ($raKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'RA',
                'question_number' => $idx + 77,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 6. SUBTES ZR (NUMBER SERIES) - Soal 97 s/d 116
        // ------------------------------------------------------------------
        $zrKeys = ['27', '25', '27', '15', '46', '10', '24', '7', '5', '14', '8', '14', '45', '36', '12', '80', '14', '12', '36', '10'];
        foreach ($zrKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'ZR',
                'question_number' => $idx + 97,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 7. SUBTES FA (FIGURE SELECTION) - Soal 117 s/d 136
        // ------------------------------------------------------------------
        $faKeys = ['a', 'c', 'b', 'a', 'd', 'b', 'c', 'e', 'e', 'd', 'e', 'b', 'd', 'c', 'b', 'a', 'b', 'd', 'c', 'c'];
        foreach ($faKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'FA',
                'question_number' => $idx + 117,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 8. SUBTES WU (CUBES / RUANG) - Soal 137 s/d 156
        // ------------------------------------------------------------------
        $wuKeys = ['a', 'c', 'd', 'e', 'a', 'c', 'd', 'c', 'e', 'a', 'b', 'd', 'e', 'b', 'd', 'b', 'a', 'e', 'b', 'c'];
        foreach ($wuKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'WU',
                'question_number' => $idx + 137,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ------------------------------------------------------------------
        // 9. SUBTES ME (MEMORY) - Soal 157 s/d 176
        // ------------------------------------------------------------------
        $meKeys = ['d', 'e', 'b', 'a', 'c', 'a', 'd', 'e', 'c', 'b', 'b', 'a', 'e', 'c', 'd', 'b', 'e', 'a', 'c', 'd'];
        foreach ($meKeys as $idx => $key) {
            $data[] = [
                'subtest' => 'ME',
                'question_number' => $idx + 157,
                'correct_answer' => $key,
                'score_weight' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Chunk insert untuk optimasi performa database
        foreach (array_chunk($data, 50) as $chunk) {
            IstAnswerKey::insert($chunk);
        }
    }
}