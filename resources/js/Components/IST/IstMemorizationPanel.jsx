import { Brain, Eye } from 'lucide-react';
import IstCountdown from '@/Components/IST/IstCountdown';
import IstStateNotice from '@/Components/IST/IstStateNotice';

export default function IstMemorizationPanel({
    content,
    countdown,
    locked = false,
    transitioning = false,
    transitionError = null,
    onRetry,
}) {
    return (
        <section className="rounded-2xl border border-indigo-500/30 bg-zinc-900/90 p-5 shadow-2xl sm:p-8">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div className="flex items-center gap-3">
                    <span className="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/10 text-indigo-300">
                        <Brain className="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p className="text-xs font-mono uppercase tracking-widest text-indigo-300">Fase menghafal</p>
                        <h1 className="text-xl font-bold text-white">Pelajari materi berikut</h1>
                    </div>
                </div>
                <IstCountdown {...countdown} label="Sisa waktu menghafal" />
            </div>

            <div className="mb-6 rounded-xl border border-zinc-800 bg-zinc-950/70 p-5 sm:p-7">
                <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-zinc-300">
                    <Eye className="h-4 w-4 text-blue-400" aria-hidden="true" />
                    Materi hafalan
                </div>
                {locked ? (
                    <div className="flex min-h-48 flex-col items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 px-5 text-center">
                        <Brain className="mb-3 h-8 w-8 text-amber-400" aria-hidden="true" />
                        <p className="font-semibold text-white">Waktu menghafal telah selesai.</p>
                        <p className="mt-2 text-sm text-amber-100">Materi dikunci dan halaman sedang beralih ke fase menjawab.</p>
                    </div>
                ) : content ? (
                    <p className="whitespace-pre-line text-base leading-8 text-zinc-100">{content}</p>
                ) : (
                    <p className="text-sm text-zinc-500">Materi hafalan belum tersedia.</p>
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
