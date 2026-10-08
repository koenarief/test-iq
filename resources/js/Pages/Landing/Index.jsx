import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'motion/react';
import { UserCheck, BrainCircuit, Briefcase, ShieldCheck, Zap, BarChart3, ArrowRight } from 'lucide-react';

const STAGES = [
    {
        title: 'Tes Gaya Kerja (DISC)',
        description: 'Mengidentifikasi tipe gaya kepribadian & perilaku kerja utama.',
        icon: UserCheck,
    },
    {
        title: 'Tes Kemampuan Kognitif Adaptasi (IST)',
        description: 'Mengukur performa pada sembilan area kemampuan kognitif.',
        icon: BrainCircuit,
    },
    {
        title: 'Tes Kompetensi',
        description: 'Studi kasus situasional sesuai jabatan/divisi yang dilamar.',
        icon: Briefcase,
    },
];

export default function LandingIndex() {
    return (
        <PublicLayout>
            <Head title="Portal Psikotes Enterprise" />

            <div className="max-w-5xl mx-auto px-6 py-12 md:py-20 flex-1 flex flex-col justify-center">
                <div className="text-center max-w-3xl mx-auto mb-12 space-y-4">
                    <motion.h1
                        initial={{ opacity: 0, y: 15 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5, delay: 0.1 }}
                        className="text-4xl md:text-6xl font-extrabold tracking-tight text-white leading-tight"
                    >
                        Selamat Datang di Portal{' '}
                        <span className="bg-gradient-to-r from-blue-400 via-indigo-300 to-purple-400 bg-clip-text text-transparent">
                            Psikotes Enterprise
                        </span>
                    </motion.h1>

                    <motion.p
                        initial={{ opacity: 0, y: 15 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5, delay: 0.2 }}
                        className="text-base md:text-lg text-zinc-400 max-w-2xl mx-auto leading-relaxed"
                    >
                        Satu rangkaian evaluasi berisi tiga tahap tes yang berjalan berurutan.
                        Isi data diri sekali di awal, lalu kerjakan ketiganya tanpa perlu
                        membuat akun atau login.
                    </motion.p>
                </div>

                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5, delay: 0.3 }}
                    className="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto w-full mb-10"
                >
                    {STAGES.map(({ title, description, icon: Icon }, index) => (
                        <div
                            key={title}
                            className="relative flex flex-col gap-3 p-6 rounded-2xl border border-zinc-800/60 bg-zinc-900/40"
                        >
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-500/10 border border-blue-500/30 text-blue-400 font-mono text-sm font-bold">
                                    {index + 1}
                                </div>
                                <Icon className="w-5 h-5 text-blue-400" />
                            </div>
                            <h3 className="text-sm font-semibold text-white">{title}</h3>
                            <p className="text-xs text-zinc-400 leading-relaxed">{description}</p>
                        </div>
                    ))}
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5, delay: 0.4 }}
                    className="flex flex-col items-center gap-4 mb-16"
                >
                    <Link
                        href={route('assessment.start')}
                        className="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-base shadow-lg shadow-blue-600/25 transition-all duration-300 group"
                    >
                        <span>Mulai Tes</span>
                        <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                    </Link>
                    <span className="text-xs font-mono text-zinc-500">
                        Estimasi total waktu: ±30 menit DISC, ±35 menit IST, ±30 menit Kompetensi
                    </span>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.6, delay: 0.5 }}
                    className="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl mx-auto w-full pt-8 border-t border-zinc-900 text-center"
                >
                    <div className="flex items-center justify-center gap-3 text-zinc-400">
                        <ShieldCheck className="w-5 h-5 text-blue-400 shrink-0" />
                        <span className="text-xs font-mono">Kerahasiaan Data Terjamin</span>
                    </div>
                    <div className="flex items-center justify-center gap-3 text-zinc-400">
                        <Zap className="w-5 h-5 text-indigo-400 shrink-0" />
                        <span className="text-xs font-mono">Pengerjaan Daring Real-time</span>
                    </div>
                    <div className="flex items-center justify-center gap-3 text-zinc-400">
                        <BarChart3 className="w-5 h-5 text-purple-400 shrink-0" />
                        <span className="text-xs font-mono">Laporan Hasil Otomatis</span>
                    </div>
                </motion.div>
            </div>
        </PublicLayout>
    );
}
