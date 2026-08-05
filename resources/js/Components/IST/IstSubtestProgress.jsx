const subtestCodes = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];

export default function IstSubtestProgress({ currentSequence = 1 }) {
    const activeSequence = Math.min(9, Math.max(1, Number(currentSequence) || 1));

    return (
        <nav aria-label="Progres sembilan subtes" className="overflow-x-auto pb-1">
            <ol className="flex min-w-max items-center gap-2">
                {subtestCodes.map((code, index) => {
                    const sequence = index + 1;
                    const isCurrent = sequence === activeSequence;
                    const isPast = sequence < activeSequence;

                    return (
                        <li key={code}>
                            <span
                                aria-current={isCurrent ? 'step' : undefined}
                                className={`inline-flex min-h-11 min-w-14 items-center justify-center rounded-xl border px-3 text-xs font-bold tracking-wider ${
                                    isCurrent
                                        ? 'border-blue-400/60 bg-blue-500/20 text-blue-200'
                                        : isPast
                                          ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                                          : 'border-zinc-800 bg-zinc-950/60 text-zinc-500'
                                }`}
                            >
                                <span className="sr-only">
                                    {isCurrent ? 'Subtes aktif: ' : isPast ? 'Subtes telah dilewati: ' : 'Subtes berikutnya: '}
                                </span>
                                {code}
                            </span>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
