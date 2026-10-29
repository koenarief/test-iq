import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'motion/react';
import { CheckCircle2, ArrowLeft, PartyPopper } from 'lucide-react';

export default function Result({ competencyTest }) {
    return (
        <PublicLayout>
            <Head title="Tes Kompetensi Selesai" />

            <div className="max-w-xl mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-1 flex flex-col justify-center items-center">
                <motion.div
                    initial={{ opacity: 0, y: 15 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4 }}
                    className="w-full bg-zinc-900/90 border border-zinc-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden text-center space-y-6"
                >
                    <div className="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 blur-3xl rounded-full pointer-events-none" />

                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono mx-auto">
                        <CheckCircle2 className="w-3.5 h-3.5" />
                        Rangkaian Tes Selesai
                    </span>

                    <div className="space-y-2">
                        <PartyPopper className="w-10 h-10 text-blue-400 mx-auto" />
                        <h2 className="text-2xl font-bold text-white">
                            Terima Kasih, {competencyTest?.participant_name}
                        </h2>
                        <p className="text-sm text-zinc-400 leading-relaxed">
                            Anda telah menyelesaikan seluruh rangkaian tes (DISC, IST, dan
                            Kompetensi). Hasil evaluasi Anda akan diproses oleh tim HR/Panitia.
                        </p>
                    </div>

                    <Link
                        href={route('landing')}
                        className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-blue-600/25 transition-all group mx-auto w-fit"
                    >
                        <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        <span>Kembali ke Beranda</span>
                    </Link>
                </motion.div>
            </div>
        </PublicLayout>
    );
}
