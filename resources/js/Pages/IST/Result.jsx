import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Award,
    BarChart3,
    CheckCircle2,
    Clock3,
    Compass,
    User,
} from 'lucide-react';
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
    const totalSeconds = Math.max(
        0,
        Math.floor(Number(value) || 0),
    );

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor(
        (totalSeconds % 3600) / 60,
    );
    const seconds = totalSeconds % 60;

    return [
        hours > 0 ? `${hours} jam` : null,
        minutes > 0 ? `${minutes} menit` : null,
        `${seconds} detik`,
    ]
        .filter(Boolean)
        .join(' ');
}

function formatNumber(value) {
    if (
        value === null
        || value === ''
        || typeof value === 'boolean'
    ) {
        return '—';
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return '—';
    }

    return new Intl.NumberFormat('id-ID').format(numeric);
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

    totalRawScore = null,
    totalStandardScore = null,
    iqScore = null,
    iqCategory = null,
    dominanceProfile = null,
}) {
    const safeSubtests =
        Array.isArray(subtests)
            ? subtests
            : [];

    const hasIqScore =
        iqScore !== null
        && iqScore !== ''
        && Number.isFinite(Number(iqScore));

    const hasCompleteSubtestResults =
        safeSubtests.length === 9;

    return (
        <PublicLayout>
            <Head title="Hasil Asesmen Kemampuan Kognitif" />

            <div className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <Link
                        href={route('landing')}
                        className="inline-flex min-h-11 items-center gap-2 rounded-lg text-xs font-mono text-zinc-400 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                    >
                        <ArrowLeft
                            className="h-4 w-4"
                            aria-hidden="true"
                        />
                        Kembali ke Beranda
                    </Link>

                    <span className="inline-flex min-h-11 items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 text-xs font-semibold text-emerald-300">
                        <CheckCircle2
                            className="h-4 w-4"
                            aria-hidden="true"
                        />
                        Asesmen selesai
                    </span>
                </div>

                <header className="mb-6 overflow-hidden rounded-2xl border border-zinc-800 bg-gradient-to-r from-blue-950/50 via-zinc-900 to-indigo-950/40 p-6 shadow-2xl sm:p-8">
                    <p className="text-xs font-mono uppercase tracking-widest text-blue-300">
                        Asesmen Kemampuan Kognitif
                    </p>

                    <h1 className="mt-2 text-2xl font-bold text-white sm:text-3xl">
                        Ringkasan Hasil
                    </h1>

                    <p className="mt-2 max-w-3xl text-sm leading-relaxed text-zinc-400">
                        Skor mentah (RW) dan skor standar (SW) sembilan
                        subtes, dikonversi ke IQ berdasarkan tabel norma
                        usia peserta.
                    </p>
                </header>

                <div className="mb-6 grid gap-4 lg:grid-cols-3">
                    <section className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5">
                        <div className="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-blue-300">
                            <User
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Biodata peserta
                        </div>

                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between gap-4 border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">
                                    Nama
                                </dt>

                                <dd className="text-right font-semibold text-white">
                                    {participant?.name ?? '-'}
                                </dd>
                            </div>

                            <div className="flex justify-between gap-4 border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">
                                    Usia
                                </dt>

                                <dd className="font-semibold text-white">
                                    {participant?.age ?? '-'} tahun
                                </dd>
                            </div>

                            <div className="flex justify-between gap-4 border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">
                                    Kelompok usia
                                </dt>

                                <dd className="text-right font-semibold text-white">
                                    {participant?.ageGroup ?? '-'}
                                </dd>
                            </div>

                            <div className="flex justify-between gap-4">
                                <dt className="text-zinc-500">
                                    Jenis kelamin
                                </dt>

                                <dd className="font-semibold text-white">
                                    {genderLabel(
                                        participant?.gender,
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5">
                        <div className="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-indigo-300">
                            <Clock3
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Waktu pelaksanaan
                        </div>

                        <dl className="space-y-3 text-sm">
                            <div className="border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">
                                    Mulai
                                </dt>

                                <dd className="mt-1 font-semibold text-white">
                                    {formatDate(
                                        startedAt,
                                    )}
                                </dd>
                            </div>

                            <div className="border-b border-zinc-800 pb-2">
                                <dt className="text-zinc-500">
                                    Selesai
                                </dt>

                                <dd className="mt-1 font-semibold text-white">
                                    {formatDate(
                                        finishedAt,
                                    )}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-zinc-500">
                                    Durasi
                                </dt>

                                <dd className="mt-1 font-semibold text-white">
                                    {formatDuration(
                                        durationSeconds,
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section className="rounded-2xl border border-blue-500/30 bg-blue-500/10 p-5">
                        <div className="mb-4 flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-blue-200">
                            <Award
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Skor IQ
                        </div>

                        <p className="text-sm text-zinc-300">
                            Hasil konversi Total SW terhadap tabel
                            norma usia.
                        </p>

                        <p className="mt-3 text-4xl font-bold text-white">
                            {hasIqScore
                                ? formatNumber(iqScore)
                                : '—'}
                        </p>

                        <div className="mt-4 space-y-2 border-t border-blue-400/20 pt-4 text-sm">
                            <div className="flex justify-between gap-4">
                                <span className="text-zinc-400">
                                    Kategori
                                </span>

                                <span className="text-right font-semibold text-white">
                                    {iqCategory ?? '—'}
                                </span>
                            </div>

                            <div className="flex justify-between gap-4">
                                <span className="text-zinc-400">
                                    Total SW
                                </span>

                                <span className="text-right font-semibold text-blue-200">
                                    {formatNumber(totalStandardScore)}
                                </span>
                            </div>

                            <div className="flex justify-between gap-4">
                                <span className="text-zinc-400">
                                    Total RW
                                </span>

                                <span className="text-right font-semibold text-blue-200">
                                    {formatNumber(totalRawScore)}
                                </span>
                            </div>
                        </div>

                        {!hasIqScore && (
                            <p className="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs leading-relaxed text-amber-100">
                                Data norma usia untuk peserta ini
                                belum tersedia. Skor RW tetap
                                tersimpan dan IQ akan tampil
                                otomatis begitu data norma dimuat.
                            </p>
                        )}
                    </section>
                </div>

                {!hasCompleteSubtestResults && (
                    <div
                        className="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm leading-relaxed text-amber-100"
                        role="status"
                    >
                        Data hasil sembilan subtes belum lengkap.
                        Beberapa nilai dapat ditampilkan sebagai
                        “—”.
                    </div>
                )}

                <section className="mb-6 rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-5">
                    <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-indigo-300">
                        <Compass className="h-4 w-4" />
                        Profil Dominasi
                    </div>

                    <p className="text-lg font-bold text-white">
                        {dominanceProfile ?? '—'}
                    </p>

                    <p className="mt-2 text-sm text-zinc-400">
                        Perbandingan SW subtes verbal (SE, WA, AN,
                        GE) terhadap subtes spasial (FA, WU, ZR,
                        RA).
                    </p>
                </section>

                <section className="mb-6 rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5 shadow-xl sm:p-7">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-blue-500/30 bg-blue-500/10 text-blue-300">
                            <BarChart3
                                className="h-5 w-5"
                                aria-hidden="true"
                            />
                        </span>

                        <div>
                            <h2
                                id="ist-result-chart-title"
                                className="text-lg font-bold text-white"
                            >
                                Profil Sembilan Subtes
                            </h2>

                            <p
                                id="ist-result-chart-description"
                                className="text-xs text-zinc-500"
                            >
                                Standard score (SW) tiap subtes,
                                rata-rata norma di 100
                            </p>
                        </div>
                    </div>

                    <IstResultChart
                        graphPoints={graphPoints}
                        subtests={safeSubtests}
                        labelledBy="ist-result-chart-title ist-result-chart-description"
                    />
                </section>

                <section className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/90 shadow-xl">
                    <div className="border-b border-zinc-800 p-5 sm:px-7">
                        <h2 className="text-lg font-bold text-white">
                            Rincian Hasil Sembilan Subtes
                        </h2>

                        <p className="mt-1 text-xs text-zinc-500">
                            Raw score (RW) dan standard score
                            (SW) pada setiap subtes.
                        </p>
                    </div>

                    {safeSubtests.length === 0 ? (
                        <p className="p-6 text-sm text-zinc-500">
                            Data hasil subtes belum
                            tersedia.
                        </p>
                    ) : (
                        <div
                            className="overflow-x-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-400"
                            tabIndex={0}
                            role="region"
                            aria-label="Detail skor sembilan subtes, gulir horizontal bila diperlukan"
                        >
                            <table className="min-w-[820px] w-full border-collapse text-left text-sm">
                                <caption className="sr-only">
                                    Rincian hasil sembilan
                                    subtes
                                </caption>

                                <thead className="bg-zinc-950/80 text-xs uppercase tracking-wider text-zinc-400">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="px-5 py-4"
                                        >
                                            Subtes
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-4 py-4 text-right"
                                        >
                                            RW
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-4 py-4 text-right"
                                        >
                                            SW
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-4 py-4 text-right"
                                        >
                                            Benar
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-4 py-4 text-right"
                                        >
                                            Parsial
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-4 py-4 text-right"
                                        >
                                            Salah
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-5 py-4 text-right"
                                        >
                                            Kosong
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-zinc-800">
                                    {safeSubtests.map(
                                        (
                                            subtest,
                                            index,
                                        ) => (
                                            <tr
                                                key={
                                                    subtest?.code
                                                    ?? index
                                                }
                                                className="text-zinc-300 hover:bg-zinc-800/30"
                                            >
                                                <th
                                                    scope="row"
                                                    className="px-5 py-4 font-semibold text-white"
                                                >
                                                    <span className="mr-2 font-mono text-blue-300">
                                                        {subtest?.code
                                                            ?? '—'}
                                                    </span>

                                                    {subtest?.name
                                                        ?? '—'}
                                                </th>

                                                <td className="px-4 py-4 text-right">
                                                    {formatNumber(
                                                        subtest?.rawScore,
                                                    )}
                                                </td>

                                                <td className="px-4 py-4 text-right font-semibold text-blue-300">
                                                    {formatNumber(
                                                        subtest?.standardScore,
                                                    )}
                                                </td>

                                                <td className="px-4 py-4 text-right">
                                                    {formatNumber(
                                                        subtest?.correctCount,
                                                    )}
                                                </td>

                                                <td className="px-4 py-4 text-right">
                                                    {formatNumber(
                                                        subtest?.partialCount,
                                                    )}
                                                </td>

                                                <td className="px-4 py-4 text-right">
                                                    {formatNumber(
                                                        subtest?.wrongCount,
                                                    )}
                                                </td>

                                                <td className="px-5 py-4 text-right">
                                                    {formatNumber(
                                                        subtest?.blankCount,
                                                    )}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </PublicLayout>
    );
}
