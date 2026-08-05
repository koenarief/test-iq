import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Award, BarChart3, CheckCircle2, Clock3, User } from 'lucide-react';
import IstResultChart from '@/Components/IST/IstResultChart';
import PublicLayout from '@/Layouts/PublicLayout';

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function formatDuration(value) {
    const totalSeconds = Math.max(0, Math.floor(Number(value) || 0));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return [hours > 0 ? `${hours} jam` : null, minutes > 0 ? `${minutes} menit` : null, `${seconds} detik`]
        .filter(Boolean)
        .join(' ');
}

function formatNumber(value, maximumFractionDigits = 3) {
    if (value === null || value === '' || typeof value === 'boolean') {
        return '—';
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return '—';
    }

    return new Intl.NumberFormat('id-ID', { maximumFractionDigits }).format(numeric);
}

function genderLabel(value) {
    if (value === 'L') return 'Laki-laki';
    if (value === 'P') return 'Perempuan';
    return '-';
}

export default function Result({
    participant = {},
    startedAt = null,
    finishedAt = null,
    durationSeconds = 0,
    subtests = [],
    graphPoints = [],
    totalInternalScore = null,
}) {
    const safeSubtests = Array.isArray(subtests) ? subtests : [];
    const hasTotalInternalScore = totalInternalScore !== null
        && totalInternalScore !== ''
        && Number.isFinite(Number(totalInternalScore));
    const totalScoreIsInRange = hasTotalInternalScore
        && Number(totalInternalScore) >= 0
        && Number(totalInternalScore) <= 100;
    const hasCompleteSubtestResults = safeSubtests.length === 9
        && safeSubtests.every((subtest) => {
            const percentage = Number(subtest?.percentage);

            return subtest?.percentage !== null
                && subtest?.percentage !== ''
                && Number.isFinite(percentage)
                && percentage >= 0
                && percentage <= 100;
        });

    return (
        <PublicLayout>
            <Head title="Hasil Tes IST" />

            <div className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <Link
                        href={route('landing')}
                        className="inline-flex min-h-11 items-center gap-2 rounded-lg text-xs font-mono text-zinc-400 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Kembali ke Beranda
                    </Link>
                    <span className="inline-flex min-h-11 items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 text-xs font-semibold text-emerald-300">
                        <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                        Evaluasi selesai
                    </span>
                </div>

                <header className="mb-6 overflow-hidden rounded-2xl border border-zinc-800 bg-gradient-to-r from-blue-950/50 via-zinc-900 to-indigo-950/40 p-6 shadow-2xl sm:p-8">
                    <p className="text-xs font-mono uppercase tracking-widest text-blue-300">Laporan internal IST</p>
                    <h1 className="mt-2 text-2xl font-bold text-white sm:text-3xl">Ringkasan Hasil Tes</h1>
                    <p className="mt-2 max-w-3xl text-sm leading-relaxed text-zinc-400">
                        Ringkasan berikut menampilkan persentase internal pada sembilan subtes yang telah final.
                    </p>
                </header>

                <div className="mb-6 grid gap-4 md:grid-cols-3">
                    <section className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5">
                        <div className="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-blue-300">
                            <User className="h-4 w-4" aria-hidden="true" />
                            Biodata peserta
                        </div>
                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between gap-4 border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">Nama</dt>
                                <dd className="text-right font-semibold text-white">{participant?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between gap-4 border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">Usia</dt>
                                <dd className="font-semibold text-white">{participant?.age ?? '-'} tahun</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-zinc-500">Jenis kelamin</dt>
                                <dd className="font-semibold text-white">{genderLabel(participant?.gender)}</dd>
                            </div>
                        </dl>
                    </section>

                    <section className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5">
                        <div className="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-indigo-300">
                            <Clock3 className="h-4 w-4" aria-hidden="true" />
                            Waktu pelaksanaan
                        </div>
                        <dl className="space-y-3 text-sm">
                            <div className="border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">Mulai</dt>
                                <dd className="mt-1 font-semibold text-white">{formatDate(startedAt)}</dd>
                            </div>
                            <div className="border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">Selesai</dt>
                                <dd className="mt-1 font-semibold text-white">{formatDate(finishedAt)}</dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500">Durasi</dt>
                                <dd className="mt-1 font-semibold text-white">{formatDuration(durationSeconds)}</dd>
                            </div>
                        </dl>
                    </section>

                    <section className="rounded-2xl border border-blue-500/30 bg-blue-500/10 p-5">
                        <div className="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-blue-200">
                            <Award className="h-4 w-4" aria-hidden="true" />
                            Rata-rata Skor Internal
                        </div>
                        <p className="text-sm text-zinc-300">Rata-rata persentase dari sembilan subtes</p>
                        <p className="mt-3 text-4xl font-bold text-white">
                            {hasTotalInternalScore ? formatNumber(totalInternalScore) : '—'}
                            {hasTotalInternalScore && <span className="ml-1 text-xl text-blue-300">%</span>}
                        </p>
                        <p className="mt-3 text-xs leading-relaxed text-zinc-400">Nilai final yang diberikan oleh sistem berdasarkan sembilan subtes.</p>
                    </section>
                </div>

                {(!hasCompleteSubtestResults || !totalScoreIsInRange) && (
                    <div className="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm leading-relaxed text-amber-100" role="status">
                        Data hasil belum lengkap atau berada di luar rentang persentase yang diharapkan. Nilai sumber tetap ditampilkan sebagai “—” atau apa adanya pada tabel agar inkonsistensi dapat diperiksa.
                    </div>
                )}

                <section className="mb-6 rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5 shadow-xl sm:p-7">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-blue-500/30 bg-blue-500/10 text-blue-300">
                            <BarChart3 className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 id="ist-result-chart-title" className="text-lg font-bold text-white">Profil sembilan subtes</h2>
                            <p id="ist-result-chart-description" className="text-xs text-zinc-500">Persentase internal 0–100</p>
                        </div>
                    </div>
                    <IstResultChart graphPoints={graphPoints} subtests={safeSubtests} />
                </section>

                <section className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/90 shadow-xl">
                    <div className="border-b border-zinc-800 p-5 sm:px-7">
                        <h2 className="text-lg font-bold text-white">Detail skor subtes</h2>
                        <p className="mt-1 text-xs text-zinc-500">Tabel ini merupakan representasi lengkap dari grafik.</p>
                    </div>

                    {safeSubtests.length === 0 ? (
                        <p className="p-6 text-sm text-zinc-500">Data hasil subtes belum tersedia.</p>
                    ) : (
                        <div className="overflow-x-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-400" tabIndex={0} role="region" aria-label="Detail skor sembilan subtes, gulir horizontal bila diperlukan">
                            <table className="min-w-[920px] w-full border-collapse text-left text-sm">
                                <caption className="sr-only">Detail skor diperoleh, skor maksimum, statistik jawaban, dan persentase untuk sembilan subtes IST.</caption>
                                <thead className="bg-zinc-950/80 text-xs uppercase tracking-wider text-zinc-400">
                                    <tr>
                                        <th scope="col" className="px-5 py-4">Subtes</th>
                                        <th scope="col" className="px-4 py-4 text-right">Skor diperoleh</th>
                                        <th scope="col" className="px-4 py-4 text-right">Skor maksimum</th>
                                        <th scope="col" className="px-4 py-4 text-right">Benar</th>
                                        <th scope="col" className="px-4 py-4 text-right">Parsial</th>
                                        <th scope="col" className="px-4 py-4 text-right">Salah</th>
                                        <th scope="col" className="px-4 py-4 text-right">Kosong</th>
                                        <th scope="col" className="px-5 py-4 text-right">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800">
                                    {safeSubtests.map((subtest, index) => (
                                        <tr key={subtest?.code ?? index} className="text-zinc-300 hover:bg-zinc-800/30">
                                            <th scope="row" className="px-5 py-4 font-semibold text-white">
                                                <span className="mr-2 font-mono text-blue-300">{subtest?.code ?? '—'}</span>
                                                {subtest?.name ?? '—'}
                                            </th>
                                            <td className="px-4 py-4 text-right">{formatNumber(subtest?.awardedScore)}</td>
                                            <td className="px-4 py-4 text-right">{formatNumber(subtest?.maxScore)}</td>
                                            <td className="px-4 py-4 text-right">{formatNumber(subtest?.correctCount, 0)}</td>
                                            <td className="px-4 py-4 text-right">{formatNumber(subtest?.partialCount, 0)}</td>
                                            <td className="px-4 py-4 text-right">{formatNumber(subtest?.wrongCount, 0)}</td>
                                            <td className="px-4 py-4 text-right">{formatNumber(subtest?.blankCount, 0)}</td>
                                            <td className="px-5 py-4 text-right font-semibold text-blue-300">
                                                {formatNumber(subtest?.percentage)}{subtest?.percentage !== null && subtest?.percentage !== '' && Number.isFinite(Number(subtest?.percentage)) ? '%' : ''}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>

                <p className="mt-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm leading-relaxed text-amber-100">
                    Hasil ini merupakan skor internal berdasarkan sembilan subtes. Nilai ini belum merupakan skor IQ atau interpretasi normatif karena tabel norma yang tervalidasi belum diterapkan.
                </p>
            </div>
        </PublicLayout>
    );
}
