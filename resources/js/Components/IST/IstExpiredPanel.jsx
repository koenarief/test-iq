import { AlertTriangle, ArrowRight } from 'lucide-react';

export default function IstExpiredPanel({ onContinue, processing = false, error = null }) {
    return (
        <section className="rounded-2xl border border-red-500/30 bg-zinc-900/90 p-6 text-center shadow-2xl sm:p-10">
            <span className="mx-auto mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl border border-red-500/30 bg-red-500/10 text-red-400">
                <AlertTriangle className="h-8 w-8" aria-hidden="true" />
            </span>
            <h1 className="text-2xl font-bold text-white">Waktu subtes telah berakhir</h1>
            <p className="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-zinc-400">
                Jawaban tidak dapat diubah lagi. Lanjutkan agar server menyelesaikan proses timeout dan mengarahkan Anda ke tahap berikutnya.
            </p>

            {error && (
                <p className="mx-auto mt-5 max-w-xl rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-200" role="alert">
                    {error}
                </p>
            )}

            <button
                type="button"
                onClick={onContinue}
                disabled={processing}
                className="mt-7 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 hover:from-blue-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950"
            >
                {processing ? 'Memproses timeout…' : error ? 'Coba Lanjutkan' : 'Lanjutkan Proses Timeout'}
                <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </button>
        </section>
    );
}
