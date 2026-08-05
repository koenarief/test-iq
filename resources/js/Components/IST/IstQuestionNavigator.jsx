import { Grid3X3 } from 'lucide-react';

export default function IstQuestionNavigator({
    questions = [],
    activeIndex = 0,
    answeredQuestionIds = new Set(),
    onSelect,
}) {
    if (questions.length === 0) {
        return null;
    }

    return (
        <aside className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-4 shadow-xl lg:sticky lg:top-32">
            <div className="mb-4 flex items-center gap-2">
                <Grid3X3 className="h-4 w-4 text-indigo-400" aria-hidden="true" />
                <h2 className="text-sm font-semibold text-white">Navigasi Soal</h2>
            </div>

            <div className="grid grid-cols-6 gap-2 sm:grid-cols-8 lg:grid-cols-4">
                {questions.map((question, index) => {
                    const answered = answeredQuestionIds.has(question?.id);
                    const active = index === activeIndex;

                    return (
                        <button
                            key={question?.id ?? index}
                            type="button"
                            onClick={() => onSelect?.(index)}
                            aria-current={active ? 'true' : undefined}
                            aria-label={`Soal ${question?.displayOrder ?? index + 1}${answered ? ', sudah dijawab' : ', belum dijawab'}`}
                            className={`min-h-11 rounded-lg border text-xs font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 ${
                                active
                                    ? 'border-blue-400 bg-blue-600 text-white'
                                    : answered
                                      ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300'
                                      : 'border-zinc-700 bg-zinc-950/70 text-zinc-400 hover:border-zinc-600 hover:text-white'
                            }`}
                        >
                            {question?.displayOrder ?? index + 1}
                        </button>
                    );
                })}
            </div>

            <div className="mt-4 flex flex-wrap gap-3 text-[11px] text-zinc-500">
                <span className="inline-flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded-sm bg-emerald-500/70" aria-hidden="true" />
                    Dijawab
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded-sm border border-zinc-600" aria-hidden="true" />
                    Kosong
                </span>
            </div>
        </aside>
    );
}
