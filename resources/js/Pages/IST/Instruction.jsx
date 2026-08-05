import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight, BookOpen, Clock3, FileQuestion, Lightbulb } from 'lucide-react';
import { useEffect, useState } from 'react';
import IstImageViewer from '@/Components/IST/IstImageViewer';
import IstStateNotice from '@/Components/IST/IstStateNotice';
import IstSubtestProgress from '@/Components/IST/IstSubtestProgress';
import PublicLayout from '@/Layouts/PublicLayout';

function formatDuration(seconds) {
    const value = Math.max(0, Number(seconds) || 0);

    if (value % 60 === 0) {
        return `${value / 60} menit`;
    }

    return `${Math.floor(value / 60)} menit ${value % 60} detik`;
}

export default function Instruction({
    subtest = {},
    examples = [],
    snapshotComplete = false,
    canStart = false,
    startUrl = null,
}) {
    const [startError, setStartError] = useState(null);
    const { post, processing } = useForm({});
    const safeExamples = Array.isArray(examples) ? examples : [];
    const startAllowed = Boolean(snapshotComplete && canStart && startUrl);
    const hasMemorizationPhase = Number(subtest?.memorizationSeconds) > 0;
    const hasAnsweringDuration = Number(subtest?.answeringSeconds) > 0;

    useEffect(() => {
        const showSafeStartError = () => {
            setStartError('Subtes belum dapat dimulai. Muat ulang halaman atau coba beberapa saat lagi.');

            return false;
        };
        const removeInvalidListener = router.on('invalid', showSafeStartError);
        const removeExceptionListener = router.on('exception', showSafeStartError);

        return () => {
            removeInvalidListener();
            removeExceptionListener();
        };
    }, []);

    const handleStart = () => {
        if (!startAllowed || processing) {
            return;
        }

        setStartError(null);
        post(startUrl, {
            preserveScroll: true,
            onError: () => setStartError('Subtes belum dapat dimulai. Muat ulang halaman atau coba beberapa saat lagi.'),
        });
    };

    return (
        <PublicLayout>
            <Head title={`Petunjuk Subtes — Tes Kemampuan Kognitif Adaptasi${subtest?.code ? ` — ${subtest.code}` : ''}`} />

            <div className="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
                <div className="mb-5">
                    <IstSubtestProgress currentSequence={subtest?.sequence} />
                </div>

                <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/90 shadow-2xl">
                    <header className="border-b border-zinc-800 bg-gradient-to-r from-blue-950/50 via-zinc-900 to-indigo-950/40 p-6 sm:p-8">
                        <p className="text-xs font-mono uppercase tracking-widest text-blue-300">Tes Kemampuan Kognitif Adaptasi</p>
                        <p className="mt-2 text-xs text-zinc-400">Subtes {subtest?.sequence ?? '-'} dari 9</p>
                        <div className="mt-2 flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <h1 className="text-2xl font-bold text-white sm:text-3xl">
                                    {subtest?.code ?? 'Subtes'} — {subtest?.name ?? 'Petunjuk Subtes'}
                                </h1>
                                <p className="mt-2 text-sm text-zinc-400">Timer baru dimulai setelah tombol Mulai Subtes ditekan.</p>
                            </div>
                            <div className="flex flex-wrap gap-2 text-xs">
                                <span className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-zinc-700 bg-zinc-950/70 px-3 text-zinc-300">
                                    <FileQuestion className="h-4 w-4 text-indigo-400" aria-hidden="true" />
                                    {subtest?.questionCount ?? 0} soal
                                </span>
                                <span className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-zinc-700 bg-zinc-950/70 px-3 text-zinc-300">
                                    <Clock3 className="h-4 w-4 text-blue-400" aria-hidden="true" />
                                    {formatDuration(subtest?.durationSeconds)}
                                </span>
                            </div>
                        </div>
                    </header>

                    <div className="space-y-7 p-6 sm:p-8">
                        {!snapshotComplete && (
                            <IstStateNotice tone="error" title="Subtes belum siap dimulai">
                                Materi soal untuk sesi ini belum lengkap. Tombol mulai dinonaktifkan agar sesi tidak berjalan dengan data yang tidak lengkap.
                            </IstStateNotice>
                        )}

                        {startError && (
                            <IstStateNotice tone="error" title="Gagal memulai subtes">{startError}</IstStateNotice>
                        )}

                        {hasMemorizationPhase && (
                            <section className="grid gap-3 sm:grid-cols-2" aria-label="Pembagian waktu subtes">
                                <div className="rounded-xl border border-indigo-500/20 bg-indigo-500/10 p-4">
                                    <p className="text-xs text-indigo-200">Fase menghafal</p>
                                    <p className="mt-1 text-lg font-bold text-white">{formatDuration(subtest?.memorizationSeconds)}</p>
                                </div>
                                <div className="rounded-xl border border-blue-500/20 bg-blue-500/10 p-4">
                                    <p className="text-xs text-blue-200">Fase menjawab</p>
                                    <p className="mt-1 text-lg font-bold text-white">{formatDuration(subtest?.answeringSeconds)}</p>
                                </div>
                            </section>
                        )}

                        {!hasMemorizationPhase && hasAnsweringDuration && (
                            <section aria-label="Durasi pengerjaan subtes">
                                <div className="rounded-xl border border-blue-500/20 bg-blue-500/10 p-4">
                                    <p className="text-xs text-blue-200">Durasi pengerjaan</p>
                                    <p className="mt-1 text-lg font-bold text-white">{formatDuration(subtest?.answeringSeconds)}</p>
                                </div>
                            </section>
                        )}

                        <section>
                            <div className="mb-3 flex items-center gap-2">
                                <BookOpen className="h-5 w-5 text-blue-400" aria-hidden="true" />
                                <h2 className="text-lg font-bold text-white">Petunjuk pengerjaan</h2>
                            </div>
                            <div className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5">
                                {subtest?.instructionContent ? (
                                    <p className="whitespace-pre-line text-sm leading-7 text-zinc-300">{subtest.instructionContent}</p>
                                ) : (
                                    <p className="text-sm text-zinc-500">Petunjuk belum tersedia.</p>
                                )}
                            </div>
                        </section>

                        <section>
                            <div className="mb-3 flex items-center gap-2">
                                <Lightbulb className="h-5 w-5 text-amber-400" aria-hidden="true" />
                                <h2 className="text-lg font-bold text-white">Contoh soal</h2>
                            </div>

                            {safeExamples.length === 0 ? (
                                <div className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5 text-sm text-zinc-500">
                                    Contoh soal belum tersedia.
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {safeExamples.map((example, exampleIndex) => (
                                        <article key={example?.displayOrder ?? exampleIndex} className="rounded-xl border border-zinc-800 bg-zinc-950/60 p-5">
                                            <p className="mb-3 text-xs font-mono uppercase tracking-wider text-blue-300">
                                                Contoh {exampleIndex + 1}
                                            </p>
                                            <p className="whitespace-pre-line text-sm leading-7 text-zinc-200">
                                                {example?.prompt ?? 'Teks contoh belum tersedia.'}
                                            </p>
                                            <IstImageViewer image={example?.image} fallbackAlt="Ilustrasi contoh soal" className="mt-4" />

                                            {Array.isArray(example?.options) && example.options.length > 0 && (
                                                <ol className="mt-4 grid gap-2 sm:grid-cols-2">
                                                    {example.options.map((option, optionIndex) => (
                                                        <li key={option?.optionKey ?? optionIndex} className="rounded-lg border border-zinc-800 bg-zinc-900/70 p-3 text-sm text-zinc-300">
                                                            <span>{option?.text ?? `Pilihan ${optionIndex + 1}`}</span>
                                                            <IstImageViewer
                                                                image={option?.image}
                                                                fallbackAlt={`Ilustrasi pilihan ${option?.optionKey ?? optionIndex + 1}`}
                                                                className="mt-2"
                                                            />
                                                        </li>
                                                    ))}
                                                </ol>
                                            )}

                                            {example?.explanation && (
                                                <div className="mt-4 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm leading-relaxed text-emerald-100">
                                                    <span className="font-semibold">Penjelasan: </span>
                                                    <span className="whitespace-pre-line">{example.explanation}</span>
                                                </div>
                                            )}
                                        </article>
                                    ))}
                                </div>
                            )}
                        </section>

                        <div className="border-t border-zinc-800 pt-6">
                            <button
                                type="button"
                                onClick={handleStart}
                                disabled={!startAllowed || processing}
                                className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 hover:from-blue-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:from-zinc-700 disabled:to-zinc-700 disabled:text-zinc-400 disabled:shadow-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                            >
                                {processing ? 'Memulai subtes…' : 'Mulai Subtes'}
                                <ArrowRight className="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
