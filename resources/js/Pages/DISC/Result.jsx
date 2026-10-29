import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'motion/react';
import { CheckCircle2, ArrowRight } from 'lucide-react';

export default function Result({ discTest }) {
    return (
        <PublicLayout>
            <Head title="Jawaban Tersimpan" />

            <div className="max-w-xl mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-1 flex flex-col justify-center items-center">
                <motion.div
                    initial={{ opacity: 0, y: 15 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4 }}
                    className="w-full bg-zinc-900/90 border border-zinc-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden text-center space-y-6"
                >
                    <div className="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 blur-3xl rounded-full pointer-events-none" />

                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10">
                        <CheckCircle2 className="w-7 h-7 text-emerald-400" />
                    </div>

                    <div className="space-y-2">
                        <h2 className="text-2xl font-bold text-white">
                            Jawaban Anda Telah Tersimpan
                        </h2>
                        <p className="text-sm text-zinc-400 leading-relaxed">
                            Terima kasih, {discTest?.participant_name}. Seluruh jawaban tes
                            DISC Anda berhasil disimpan. Silakan lanjutkan ke Tes Kemampuan
                            Kognitif (IST) saat Anda sudah siap.
                        </p>
                    </div>

                    <Link
                        href={route('assessment.continue.ist')}
                        method="post"
                        as="button"
                        className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-blue-600/25 transition-all group mx-auto"
                    >
                        <span>Lanjutkan Sekarang</span>
                        <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </motion.div>
            </div>
        </PublicLayout>
    );
}
