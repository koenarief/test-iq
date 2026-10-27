import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    Clock3,
    User,
} from 'lucide-react';
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
}) {
    return (
        <PublicLayout>
            <Head title="Asesmen Kemampuan Kognitif Selesai" />

            <div className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
                <div className="mb-6 flex flex-wrap items-center justify-center gap-4">
                    <span className="inline-flex min-h-11 items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 text-xs font-semibold text-emerald-300">
                        <CheckCircle2
                            className="h-4 w-4"
                            aria-hidden="true"
                        />
                        Asesmen selesai
                    </span>
                </div>

                <header className="mb-6 overflow-hidden rounded-2xl border border-zinc-800 bg-gradient-to-r from-blue-950/50 via-zinc-900 to-indigo-950/40 p-6 text-center shadow-2xl sm:p-10">
                    <p className="text-xs font-mono uppercase tracking-widest text-blue-300">
                        Asesmen Kemampuan Kognitif
                    </p>

                    <h1 className="mt-2 text-2xl font-bold text-white sm:text-3xl">
                        Terima Kasih, Jawaban Anda Telah Tersimpan
                    </h1>

                    <p className="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-zinc-400">
                        Seluruh sembilan subtes telah selesai dikerjakan. Hasil
                        penilaian akan diproses oleh tim HRD dan tidak
                        ditampilkan pada halaman ini.
                    </p>
                </header>

                <div className="mb-6 grid gap-4 sm:grid-cols-2">
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
                </div>

                <div className="flex justify-center">
                    <Link
                        href={route('assessment.continue.competency')}
                        method="post"
                        as="button"
                        className="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 text-base font-bold text-white shadow-xl shadow-blue-600/30 hover:from-blue-500 hover:to-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 sm:w-auto sm:px-10"
                    >
                        Lanjutkan ke Tes Kompetensi
                        <ArrowRight
                            className="h-5 w-5"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </div>
        </PublicLayout>
    );
}
