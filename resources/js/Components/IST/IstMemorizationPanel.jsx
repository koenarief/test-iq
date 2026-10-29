import IstCountdown from '@/Components/IST/IstCountdown';
import IstStateNotice from '@/Components/IST/IstStateNotice';

export default function IstMemorizationPanel({
    groups = [],
    countdown,
    locked = false,
    transitioning = false,
    transitionError = null,
    onRetry,
}) {
    const safeGroups = Array.isArray(groups) ? groups : [];
    return (
        <section className="rounded-2xl border border-indigo-500/30 bg-zinc-900/90 p-3 shadow-2xl sm:p-8">
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-zinc-800 pb-3 sm:mb-6 sm:gap-4 sm:pb-5">
                <div>
                        <p className="text-[10px] font-mono uppercase tracking-widest text-indigo-300 sm:text-xs">Fase menghafal</p>
                        <h1 className="text-base font-bold text-white sm:text-xl">Pelajari materi berikut</h1>
                </div>
                <IstCountdown {...countdown} label="Sisa waktu menghafal" />
            </div>

            <div className="mb-3 rounded-xl border border-zinc-800 bg-zinc-950/70 p-2 sm:mb-6 sm:p-7">
                <p className="mb-2 text-xs font-semibold text-zinc-300 sm:mb-4 sm:text-sm">Materi hafalan</p>
                {locked ? (
                    <div className="flex min-h-24 flex-col items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 px-5 text-center sm:min-h-48">
                        <p className="text-sm font-semibold text-white sm:text-base">Waktu menghafal telah selesai.</p>
                        <p className="mt-2 text-xs text-amber-100 sm:text-sm">Materi dikunci dan halaman sedang beralih ke fase menjawab.</p>
                    </div>
                ) : safeGroups.length > 0 ? (
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-4 xl:grid-cols-5" aria-label="Lima kelompok kata untuk dihafalkan">
                        {safeGroups.map((group, index) => (
                            <section
                                key={`${group?.key ?? index}:${group?.name ?? ''}`}
                                className="rounded-lg border border-zinc-700 bg-zinc-900 p-1.5 sm:rounded-xl sm:p-4"
                            >
                                <div className="mb-1 flex items-center gap-1.5 sm:mb-3 sm:gap-2">
                                    <span className="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-indigo-500/30 bg-indigo-500/10 text-[10px] font-bold text-indigo-300 sm:h-7 sm:w-7 sm:rounded-lg sm:text-xs">
                                        {group?.key ?? index + 1}
                                    </span>
                                    <h2 className="text-xs font-semibold text-white sm:text-base">
                                        {group?.name ?? 'Kelompok'}
                                    </h2>
                                </div>

                                <ul className="space-y-1 sm:space-y-2">
                                    {(Array.isArray(group?.words) ? group.words : []).map((word) => (
                                        <li
                                            key={word}
                                            className="rounded-md border border-zinc-700 bg-zinc-950 px-1.5 py-1 text-[11px] font-medium leading-tight text-zinc-100 sm:rounded-lg sm:px-3 sm:py-2 sm:text-sm sm:leading-normal"
                                        >
                                            {word}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                ) : (
                    <IstStateNotice tone="error" title="Materi hafalan belum tersedia">
                        Sesi ini tidak mempunyai snapshot lima kelompok kata yang valid.
                    </IstStateNotice>
                )}
            </div>

            {transitionError ? (
                <IstStateNotice tone="error" title="Gagal beralih ke fase menjawab">
                    <p>{transitionError}</p>
                    <button
                        type="button"
                        onClick={onRetry}
                        className="mt-3 min-h-11 rounded-lg border border-red-300/30 px-4 font-semibold hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                    >
                        Coba Lagi
                    </button>
                </IstStateNotice>
            ) : (
                <IstStateNotice title={transitioning ? 'Mengalihkan fase…' : 'Fase menghafal berlangsung'}>
                    {transitioning
                        ? 'Server sedang menentukan state fase menjawab. Tidak ada waktu tambahan yang dibuat oleh browser.'
                        : 'Setelah waktu habis, materi akan dikunci dan halaman berpindah otomatis ke fase menjawab.'}
                </IstStateNotice>
            )}
        </section>
    );
}
