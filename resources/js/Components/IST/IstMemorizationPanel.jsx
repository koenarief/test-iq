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
        <section className="rounded-2xl border border-indigo-500/30 bg-zinc-900/90 p-5 shadow-2xl sm:p-8">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                        <p className="text-xs font-mono uppercase tracking-widest text-indigo-300">Fase menghafal</p>
                        <h1 className="text-xl font-bold text-white">Pelajari materi berikut</h1>
                </div>
                <IstCountdown {...countdown} label="Sisa waktu menghafal" />
            </div>

            <div className="mb-6 rounded-xl border border-zinc-800 bg-zinc-950/70 p-5 sm:p-7">
                <p className="mb-4 text-sm font-semibold text-zinc-300">Materi hafalan</p>
                {locked ? (
                    <div className="flex min-h-48 flex-col items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 px-5 text-center">
                        <p className="font-semibold text-white">Waktu menghafal telah selesai.</p>
                        <p className="mt-2 text-sm text-amber-100">Materi dikunci dan halaman sedang beralih ke fase menjawab.</p>
                    </div>
                ) : safeGroups.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Lima kelompok kata untuk dihafalkan">
                        {safeGroups.map((group, index) => (
                            <section
                                key={`${group?.key ?? index}:${group?.name ?? ''}`}
                                className="rounded-xl border border-zinc-700 bg-zinc-900 p-4"
                            >
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-indigo-500/30 bg-indigo-500/10 text-xs font-bold text-indigo-300">
                                        {group?.key ?? index + 1}
                                    </span>
                                    <h2 className="font-semibold text-white">
                                        {group?.name ?? 'Kelompok'}
                                    </h2>
                                </div>

                                <ul className="space-y-2">
                                    {(Array.isArray(group?.words) ? group.words : []).map((word) => (
                                        <li
                                            key={word}
                                            className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm font-medium text-zinc-100"
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
