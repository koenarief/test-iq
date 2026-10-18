<?php

namespace App\Console\Commands;

use App\Models\Ist\IstQuestion;
use App\Services\Ist\IstQuestionOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenumberIstQuestions extends Command
{
    protected $signature = 'ist:renumber-questions {--apply : Simpan perapian nomor dan urutan tampil}';

    protected $description = 'Pratinjau atau rapikan nomor bank soal IST per subtes dan jenis (tanpa mengubah snapshot peserta)';

    public function handle(IstQuestionOrderService $ordering): int
    {
        $changes = DB::transaction(function () use ($ordering) {
            $ordering->lock();
            $changes = 0;
            $groups = IstQuestion::query()->with('subtest')->orderBy('question_number')->orderBy('id')
                ->get()->groupBy(fn ($question) => $question->ist_subtest_id.'|'.$question->kind);
            foreach ($groups as $questions) {
                $different = $questions->values()->filter(fn ($question, $index) => $question->question_number !== $index + 1 || $question->display_order !== $index + 1
                )->count();
                if (! $different) {
                    continue;
                }
                $first = $questions->first();
                $this->line("{$first->subtest->code} / {$first->kind}: {$different} soal perlu dirapikan.");
                $changes += $different;
                if ($this->option('apply')) {
                    $ordering->normalize($first->ist_subtest_id, $first->kind);
                }
            }

            return $changes;
        });
        $this->info($this->option('apply')
            ? "Selesai: {$changes} soal dirapikan."
            : "Pratinjau: {$changes} soal. Gunakan --apply untuk menyimpan.");

        return self::SUCCESS;
    }
}
