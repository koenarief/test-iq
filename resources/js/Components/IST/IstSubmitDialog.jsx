import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { AlertTriangle, X } from 'lucide-react';

export default function IstSubmitDialog({
    open = false,
    answeredCount = 0,
    blankCount = 0,
    onClose,
    onConfirm,
    processing = false,
}) {
    return (
        <Dialog open={open} onClose={processing ? () => {} : onClose} className="relative z-50">
            <div className="fixed inset-0 bg-zinc-950/85 backdrop-blur-sm" aria-hidden="true" />
            <div className="fixed inset-0 overflow-y-auto p-4">
                <div className="flex min-h-full items-center justify-center">
                    <DialogPanel className="w-full max-w-lg rounded-2xl border border-zinc-700 bg-zinc-900 p-5 shadow-2xl sm:p-7">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex items-start gap-3">
                                <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400">
                                    <AlertTriangle className="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <DialogTitle className="text-xl font-bold text-white">
                                        Selesaikan subtes?
                                    </DialogTitle>
                                    <p className="mt-1 text-sm leading-relaxed text-zinc-400">
                                        Setelah diselesaikan, subtes akan dikunci permanen dan Anda tidak dapat kembali.
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={onClose}
                                disabled={processing}
                                className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-zinc-700 text-zinc-400 hover:bg-zinc-800 hover:text-white disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                aria-label="Tutup dialog konfirmasi"
                            >
                                <X className="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>

                        <dl className="my-6 grid grid-cols-2 gap-3">
                            <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-center">
                                <dt className="text-xs text-emerald-200">Sudah dijawab</dt>
                                <dd className="mt-1 text-2xl font-bold text-white">{answeredCount}</dd>
                            </div>
                            <div className="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-center">
                                <dt className="text-xs text-amber-200">Masih kosong</dt>
                                <dd className="mt-1 text-2xl font-bold text-white">{blankCount}</dd>
                            </div>
                        </dl>

                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={onClose}
                                disabled={processing}
                                className="min-h-11 rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-semibold text-zinc-300 hover:border-zinc-600 hover:text-white disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={onConfirm}
                                disabled={processing}
                                className="min-h-11 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 text-sm font-semibold text-white hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                            >
                                {processing ? 'Memproses…' : 'Konfirmasi Selesai'}
                            </button>
                        </div>
                    </DialogPanel>
                </div>
            </div>
        </Dialog>
    );
}
