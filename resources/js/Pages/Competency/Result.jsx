import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'motion/react';
import {
    User,
    Award,
    BarChart3,
    ArrowLeft,
    CheckCircle2,
    Briefcase,
} from 'lucide-react';

function ScoreBar({ label, percentage, avgPoints, answeredCount }) {
    return (
        <div className="space-y-1.5">
            <div className="flex items-center justify-between gap-3 text-xs sm:text-sm">
                <span className="font-medium text-zinc-200">{label}</span>
                <span className="font-mono text-zinc-400">
                    {avgPoints.toFixed(2)} / 5{' '}
                    <span className="text-zinc-600">
                        ({answeredCount} soal)
                    </span>
                </span>
            </div>
            <div className="w-full h-2.5 bg-zinc-950 rounded-full overflow-hidden border border-zinc-800">
                <div
                    className="h-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-500"
                    style={{ width: `${Math.min(100, percentage)}%` }}
                />
            </div>
        </div>
    );
}

export default function Result({ competencyTest, breakdown = [], overallPercentage }) {
    return (
        <PublicLayout>
            <Head title="Hasil Tes Kompetensi" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-1 flex flex-col justify-center items-center">
                <div className="w-full max-w-4xl space-y-6">
                    {/* Top Navigation & Status Badge */}
                    <div className="flex items-center justify-between">
                        <Link
                            href={route('landing')}
                            className="inline-flex items-center gap-2 text-xs font-mono text-zinc-400 hover:text-white transition-colors group"
                        >
                            <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                            <span>Selesai & Kembali ke Beranda</span>
                        </Link>

                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono">
                            <CheckCircle2 className="w-3.5 h-3.5" />
                            Evaluasi Selesai
                        </span>
                    </div>

                    {/* Main Container Card */}
                    <motion.div
                        initial={{ opacity: 0, y: 15 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                        className="bg-zinc-900/90 border border-zinc-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden space-y-8"
                    >
                        <div className="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 blur-3xl rounded-full pointer-events-none" />

                        {/* Page Header */}
                        <div className="pb-6 border-b border-zinc-800">
                            <span className="text-xs font-mono text-blue-400 uppercase tracking-widest block mb-1">
                                Langkah 3 dari 3 - Hasil Evaluasi
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                                Laporan Hasil Tes Kompetensi
                            </h2>
                            <p className="text-xs sm:text-sm text-zinc-400 mt-1 leading-relaxed">
                                Berikut rangkuman skor kompetensi Anda pada tiap subtes divisi{' '}
                                {competencyTest.department_label}.
                            </p>
                        </div>

                        {/* Participant Biodata & Result Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Participant Bio Card */}
                            <div className="p-5 rounded-xl bg-zinc-950/60 border border-zinc-800/80 space-y-3">
                                <div className="flex items-center gap-2 text-xs font-mono text-blue-400 uppercase tracking-wider mb-2">
                                    <User className="w-4 h-4" />
                                    <span>Biodata Peserta</span>
                                </div>

                                <div className="space-y-2.5 text-sm">
                                    <div className="flex justify-between items-center py-1 border-b border-zinc-800/50">
                                        <span className="text-zinc-500 text-xs">Nama Lengkap</span>
                                        <span className="font-semibold text-white">{competencyTest?.participant_name}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-1 border-b border-zinc-800/50">
                                        <span className="text-zinc-500 text-xs">Usia</span>
                                        <span className="font-semibold text-white">{competencyTest?.age} Tahun</span>
                                    </div>
                                    <div className="flex justify-between items-center py-1">
                                        <span className="text-zinc-500 text-xs">Jenis Kelamin</span>
                                        <span className="font-semibold text-white">
                                            {competencyTest?.gender === 'L' ? 'Laki-laki' : competencyTest?.gender === 'P' ? 'Perempuan' : competencyTest?.gender}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Score Summary Card */}
                            <div className="p-5 rounded-xl bg-zinc-950/60 border border-zinc-800/80 space-y-3">
                                <div className="flex items-center gap-2 text-xs font-mono text-indigo-400 uppercase tracking-wider mb-2">
                                    <Award className="w-4 h-4" />
                                    <span>Skor Kompetensi</span>
                                </div>

                                <div className="space-y-2.5 text-sm">
                                    <div className="flex justify-between items-center py-1 border-b border-zinc-800/50">
                                        <span className="text-zinc-500 text-xs">Divisi</span>
                                        <span className="inline-flex items-center gap-1.5 font-mono font-bold text-sm text-blue-400 px-3 py-0.5 rounded-md bg-blue-500/10 border border-blue-500/30">
                                            <Briefcase className="w-3.5 h-3.5" />
                                            {competencyTest?.department_label}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center py-1 border-b border-zinc-800/50">
                                        <span className="text-zinc-500 text-xs">Skor Rata-rata</span>
                                        <span className="font-semibold text-zinc-200">
                                            {Number(competencyTest?.total_score ?? 0).toFixed(2)} / 5
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center py-1">
                                        <span className="text-zinc-500 text-xs">Persentase</span>
                                        <span className="font-semibold text-zinc-200">{overallPercentage}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Breakdown Chart Section */}
                        <div className="p-5 sm:p-6 rounded-xl bg-zinc-950/60 border border-zinc-800/80 space-y-5">
                            <div className="flex items-center gap-2">
                                <div className="p-2 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                    <BarChart3 className="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 className="text-base font-bold text-white">Skor per Subtes</h3>
                                    <p className="text-xs text-zinc-400">
                                        Rata-rata poin (skala 1-5) untuk tiap kompetensi yang diuji
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-5 pt-2">
                                {breakdown.map((category) => (
                                    <ScoreBar
                                        key={category.category_id}
                                        label={category.category_name}
                                        percentage={category.percentage}
                                        avgPoints={category.avg_points}
                                        answeredCount={category.answered_count}
                                    />
                                ))}

                                {breakdown.length === 0 && (
                                    <p className="text-sm text-zinc-500">
                                        Belum ada data skor untuk ditampilkan.
                                    </p>
                                )}
                            </div>
                        </div>
                    </motion.div>
                </div>
            </div>
        </PublicLayout>
    );
}
