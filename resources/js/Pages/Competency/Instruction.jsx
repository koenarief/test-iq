import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'motion/react';
import { Clock, CheckSquare, AlertCircle, ArrowRight, ArrowLeft, UserCheck, ListChecks } from 'lucide-react';

export default function Instruction({ competencyTest, categories = [], totalQuestions, durationMinutes }) {
    return (
        <PublicLayout>
            <Head title="Instruksi Tes Kompetensi" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-1 flex flex-col justify-center items-center">
                <div className="w-full max-w-2xl">
                    {/* Back Button */}
                    <div className="mb-6">
                        <Link
                            href={route('competency.index')}
                            className="inline-flex items-center gap-2 text-xs font-mono text-zinc-400 hover:text-white transition-colors group"
                        >
                            <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                            <span>Kembali ke Edit Biodata</span>
                        </Link>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, y: 15 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4 }}
                        className="bg-zinc-900/90 border border-zinc-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden"
                    >
                        <div className="absolute top-0 right-0 w-48 h-48 bg-blue-600/10 blur-3xl rounded-full pointer-events-none" />

                        {/* Step Header & Participant Greeting */}
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-zinc-800 mb-6 gap-4">
                            <div>
                                <span className="text-xs font-mono text-blue-400 uppercase tracking-widest block mb-1">
                                    Langkah 2 dari 3
                                </span>
                                <h2 className="text-xl sm:text-2xl font-bold text-white tracking-tight">
                                    Petunjuk Pengerjaan Tes Kompetensi
                                </h2>
                            </div>
                            <div className="sm:text-right bg-zinc-950/60 p-3 sm:p-0 rounded-xl border sm:border-0 border-zinc-800">
                                <span className="text-xs text-zinc-500 font-mono block">Peserta:</span>
                                <span className="text-sm font-semibold text-zinc-200">
                                    {competencyTest.participant_name} ({competencyTest.age} thn, {competencyTest.gender === 'L' ? 'Laki-laki' : 'Perempuan'})
                                </span>
                                <span className="mt-1 inline-flex items-center gap-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 px-2.5 py-0.5 text-[11px] font-medium text-blue-300">
                                    Divisi: {competencyTest.department_label}
                                </span>
                            </div>
                        </div>

                        {/* Rules & Guidelines Grid */}
                        <div className="space-y-4 mb-8">
                            <div className="flex items-start gap-4 p-4 rounded-xl bg-zinc-950/60 border border-zinc-800/80">
                                <div className="p-2.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 shrink-0">
                                    <Clock className="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-white mb-1">
                                        Alokasi Waktu {durationMinutes} Menit
                                    </h4>
                                    <p className="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                        Timer hitung mundur {durationMinutes} menit akan dimulai tepat setelah Anda menekan tombol di bawah ini. Selesaikan seluruh soal sebelum waktu habis.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4 p-4 rounded-xl bg-zinc-950/60 border border-zinc-800/80">
                                <div className="p-2.5 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 shrink-0">
                                    <CheckSquare className="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-white mb-1">
                                        {totalQuestions} Soal Studi Kasus
                                    </h4>
                                    <p className="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                        Setiap soal berupa studi kasus kerja dengan 5 pilihan jawaban (A-E). Pilih 1 jawaban yang paling menggambarkan tindakan Anda.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4 p-4 rounded-xl bg-zinc-950/60 border border-zinc-800/80">
                                <div className="p-2.5 rounded-lg bg-purple-500/10 border border-purple-500/30 text-purple-400 shrink-0">
                                    <ListChecks className="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-white mb-1">
                                        {categories.length} Subtes Divisi {competencyTest.department_label}
                                    </h4>
                                    <ul className="text-xs sm:text-sm text-zinc-400 leading-relaxed space-y-1">
                                        {categories.map((category) => (
                                            <li key={category.id} className="flex items-center justify-between gap-3">
                                                <span>{category.name}</span>
                                                <span className="text-zinc-500 font-mono text-[11px]">
                                                    {category.question_count} soal
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>

                            <div className="flex items-start gap-4 p-4 rounded-xl bg-zinc-950/60 border border-zinc-800/80">
                                <div className="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-400 shrink-0">
                                    <AlertCircle className="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-white mb-1">Jawab Sesuai Situasi Kerja Nyata</h4>
                                    <p className="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                        Tidak ada jawaban benar atau salah secara mutlak. Pilihlah tindakan yang paling mendekati cara Anda merespons situasi tersebut di dunia kerja.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* CTA Start Test */}
                        <div>
                            <Link
                                href={route('competency.test', competencyTest.id)}
                                className="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-blue-600/25 transition-all duration-300 group cursor-pointer"
                            >
                                <UserCheck className="w-4 h-4" />
                                <span>Saya Mengerti & Mulai Tes</span>
                                <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </Link>
                        </div>
                    </motion.div>
                </div>
            </div>
        </PublicLayout>
    );
}
