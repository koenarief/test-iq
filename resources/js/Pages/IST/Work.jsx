import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Flag, RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import IstAutosaveStatus from '@/Components/IST/IstAutosaveStatus';
import IstCountdown from '@/Components/IST/IstCountdown';
import IstExpiredPanel from '@/Components/IST/IstExpiredPanel';
import IstMemorizationPanel from '@/Components/IST/IstMemorizationPanel';
import IstQuestionCard from '@/Components/IST/IstQuestionCard';
import IstQuestionNavigator from '@/Components/IST/IstQuestionNavigator';
import IstStateNotice from '@/Components/IST/IstStateNotice';
import IstSubmitDialog from '@/Components/IST/IstSubmitDialog';
import IstSubtestProgress from '@/Components/IST/IstSubtestProgress';
import useIstAutosave from '@/Hooks/IST/useIstAutosave';
import useServerCountdown from '@/Hooks/IST/useServerCountdown';
import PublicLayout from '@/Layouts/PublicLayout';

const EMPTY_QUESTIONS = [];
const NUMERIC_TRANSPORT_PATTERN = /^[+-]?(?:\d+(?:\.\d{0,6})?|\.\d{1,6})$/;

function normalizedRevision(value) {
    const revision = Number(value);

    return Number.isInteger(revision) && revision >= 0 ? revision : 0;
}

function initialAnswers(questions) {
    return questions.reduce((answers, question) => {
        if (!question?.id) {
            return answers;
        }

        const revision = normalizedRevision(question?.savedAnswer?.clientRevision);

        answers[question.id] = {
            selectedOptionKey: question?.savedAnswer?.selectedOptionKey ?? null,
            numericAnswer: question?.savedAnswer?.numericAnswer ?? '',
            clientRevision: revision,
            persistedRevision: revision,
            dirty: false,
            validationError: null,
        };

        return answers;
    }, {});
}

function isAnswered(question, answer) {
    if (question?.answerType === 'numeric') {
        return String(answer?.numericAnswer ?? '').trim() !== '';
    }

    return Boolean(answer?.selectedOptionKey);
}

function numericValidation(value) {
    const numeric = String(value ?? '');
    const trimmed = numeric.trim();

    if (trimmed === '') {
        return null;
    }

    if (!NUMERIC_TRANSPORT_PATTERN.test(trimmed)) {
        return 'Lengkapi jawaban sebagai angka biasa dengan maksimal enam angka desimal.';
    }

    const unsigned = trimmed.replace(/^[+-]/, '');
    const integerPart = (unsigned.split('.')[0] || '0').replace(/^0+/, '') || '0';

    return integerPart.length <= 14
        ? null
        : 'Jawaban numerik terlalu panjang.';
}

function answerPayload(question, answer) {
    const base = {
        ist_test_question_id: Number(question.id),
        client_revision: normalizedRevision(answer?.clientRevision),
    };

    if (question?.answerType === 'numeric') {
        const value = String(answer?.numericAnswer ?? '');

        return {
            ...base,
            numeric_answer: value.trim() === '' ? null : value,
        };
    }

    return {
        ...base,
        selected_option_key: answer?.selectedOptionKey ?? null,
    };
}

export default function Work({
    testPublicId = null,
    mode = 'expired',
    subtest = {},
    memorizationGroups = [],
    questions = EMPTY_QUESTIONS,
    serverTime = null,
    phaseEndsAt = null,
    remainingSeconds: fallbackSeconds = 0,
    autosaveUrl = null,
    finishUrl = null,
}) {
    const safeQuestions = useMemo(() => Array.isArray(questions) ? questions : EMPTY_QUESTIONS, [questions]);
    const resetKey = `${testPublicId ?? 'unknown'}:${subtest?.code ?? 'unknown'}:${mode}`;
    const [answers, setAnswers] = useState(() => initialAnswers(safeQuestions));
    const [currentIndex, setCurrentIndex] = useState(0);
    const [submitDialogOpen, setSubmitDialogOpen] = useState(false);
    const [finalizing, setFinalizing] = useState(false);
    const [finalized, setFinalized] = useState(false);
    const [finalizationError, setFinalizationError] = useState(null);
    const [runtimeConflict, setRuntimeConflict] = useState(null);
    const [sessionUnavailable, setSessionUnavailable] = useState(false);
    const [timeoutError, setTimeoutError] = useState(null);
    const [transitioning, setTransitioning] = useState(false);
    const [transitionError, setTransitionError] = useState(null);
    const [timerAnnouncement, setTimerAnnouncement] = useState('');
    const finalizationRequestRef = useRef(false);
    const finalizationActionRef = useRef(null);
    const timeoutAutoAttemptedRef = useRef(false);
    const transitionAttemptedRef = useRef(false);
    const countdownExpiredRef = useRef(false);
    const warningThresholdRef = useRef(new Set());
    const errorNoticeRef = useRef(null);

    const countdown = useServerCountdown({
        serverTime,
        phaseEndsAt,
        fallbackSeconds,
    });
    countdownExpiredRef.current = countdown.isExpired;

    useEffect(() => {
        setAnswers(initialAnswers(safeQuestions));
        setCurrentIndex(0);
        setSubmitDialogOpen(false);
        setFinalizing(false);
        setFinalized(false);
        setFinalizationError(null);
        setRuntimeConflict(null);
        setSessionUnavailable(false);
        setTimeoutError(null);
        setTransitioning(false);
        setTransitionError(null);
        timeoutAutoAttemptedRef.current = false;
        transitionAttemptedRef.current = false;
        finalizationRequestRef.current = false;
        finalizationActionRef.current = null;
        warningThresholdRef.current = new Set();
    }, [resetKey, safeQuestions]);

    const handlePersisted = useCallback((sentBatch, acceptedIds) => {
        const sentByQuestion = new Map(sentBatch.map((change) => [change.ist_test_question_id, change]));
        const accepted = new Set(acceptedIds);

        setAnswers((current) => {
            const next = { ...current };

            accepted.forEach((questionId) => {
                const local = current[questionId];
                const sent = sentByQuestion.get(questionId);

                if (local && sent && local.clientRevision === sent.client_revision) {
                    next[questionId] = {
                        ...local,
                        persistedRevision: sent.client_revision,
                        dirty: false,
                    };
                }
            });

            return next;
        });
    }, []);

    const handleAutosaveConflict = useCallback((conflict) => {
        setRuntimeConflict(conflict?.type === 'state'
            ? 'State subtes berubah di server. Muat ulang untuk mengikuti tujuan canonical.'
            : 'Jawaban yang lebih baru terdeteksi. Muat ulang untuk menggunakan state canonical server.');
    }, []);

    const autosave = useIstAutosave({
        autosaveUrl,
        enabled: mode === 'answering' && countdown.hasValidDeadline && !countdown.isExpired,
        expired: mode === 'expired' || (mode === 'answering' && countdown.isExpired),
        resetKey,
        onPersisted: handlePersisted,
        onConflict: handleAutosaveConflict,
        onSessionUnavailable: () => setSessionUnavailable(true),
        onValidationError: () => setFinalizationError('Jawaban ditolak oleh validasi. Periksa kembali format jawaban.'),
    });

    const answeredQuestionIds = useMemo(() => new Set(
        safeQuestions
            .filter((question) => isAnswered(question, answers[question?.id]))
            .map((question) => question.id),
    ), [answers, safeQuestions]);

    const hasLocalDirtyAnswers = useMemo(
        () => Object.values(answers).some((answer) => answer?.dirty),
        [answers],
    );
    const answeredCount = answeredQuestionIds.size;
    const blankCount = Math.max(0, safeQuestions.length - answeredCount);
    const currentQuestion = safeQuestions[currentIndex] ?? null;
    const answeringExpired = mode === 'answering' && countdown.isExpired;
    const showExpiredPanel = mode === 'expired' || answeringExpired;
    const inputLocked = mode !== 'answering'
        || !countdown.hasValidDeadline
        || countdown.isExpired
        || Boolean(autosave.conflict)
        || Boolean(runtimeConflict)
        || sessionUnavailable
        || finalizing
        || finalized;
    const lockReason = sessionUnavailable
        ? 'Sesi tidak tersedia. Jawaban tidak dapat diubah.'
        : runtimeConflict || autosave.conflict
          ? 'Jawaban dikunci karena state server perlu dimuat ulang.'
          : !countdown.hasValidDeadline
            ? 'Deadline server tidak valid. Jawaban dikunci untuk mencegah perubahan di luar waktu resmi.'
            : countdown.isExpired
              ? 'Waktu telah habis. Jawaban tidak dapat diubah.'
              : finalizing || finalized
                ? 'Subtes sedang diselesaikan dan jawaban telah dikunci.'
                : null;
    const displayedAutosaveStatus = showExpiredPanel
        ? 'expired'
        : autosave.conflict || runtimeConflict
          ? 'conflict'
          : hasLocalDirtyAnswers && ['idle', 'saved'].includes(autosave.status)
            ? 'dirty'
            : autosave.status;

    useEffect(() => {
        if (runtimeConflict || sessionUnavailable || finalizationError) {
            errorNoticeRef.current?.focus();
        }
    }, [finalizationError, runtimeConflict, sessionUnavailable]);

    useEffect(() => {
        if (mode !== 'answering' || !countdown.hasValidDeadline || countdown.isExpired) {
            return;
        }

        for (const threshold of [300, 60]) {
            if (countdown.remainingSeconds <= threshold && !warningThresholdRef.current.has(threshold)) {
                warningThresholdRef.current.add(threshold);
                setTimerAnnouncement(threshold === 60 ? 'Sisa waktu satu menit.' : 'Sisa waktu lima menit.');
                break;
            }
        }
    }, [countdown.hasValidDeadline, countdown.isExpired, countdown.remainingSeconds, mode]);

    const reloadCanonicalState = useCallback(() => {
        router.visit(window.location.href, {
            method: 'get',
            replace: true,
            preserveScroll: true,
            preserveState: false,
        });
    }, []);

    const handleAnswerChange = (nextValue) => {
        if (!currentQuestion?.id || inputLocked) {
            return;
        }

        const current = answers[currentQuestion.id] ?? {
            clientRevision: 0,
            persistedRevision: 0,
        };
        const nextRevision = current.clientRevision + 1;
        const validationError = currentQuestion.answerType === 'numeric'
            ? numericValidation(nextValue.numericAnswer)
            : null;
        const nextAnswer = {
            ...current,
            selectedOptionKey: nextValue.selectedOptionKey ?? null,
            numericAnswer: nextValue.numericAnswer ?? '',
            clientRevision: nextRevision,
            dirty: true,
            validationError,
        };

        setAnswers((existing) => ({
            ...existing,
            [currentQuestion.id]: nextAnswer,
        }));
        setFinalizationError(null);

        if (!validationError) {
            autosave.enqueue(answerPayload(currentQuestion, nextAnswer));
        }
    };

    const buildFinalAnswers = useCallback(() => safeQuestions.map(
        (question) => answerPayload(question, answers[question.id]),
    ), [answers, safeQuestions]);

    const handleManualSubmit = async () => {
        if (
            finalizationRequestRef.current
            || finalizing
            || finalized
            || showExpiredPanel
            || runtimeConflict
            || autosave.conflict
            || sessionUnavailable
            || !finishUrl
        ) {
            return;
        }

        const invalidQuestionIndex = safeQuestions.findIndex(
            (question) => Boolean(answers[question.id]?.validationError),
        );

        if (invalidQuestionIndex >= 0) {
            setSubmitDialogOpen(false);
            setCurrentIndex(invalidQuestionIndex);
            setFinalizationError(
                'Perbaiki jawaban numerik yang belum lengkap sebelum menyelesaikan subtes.',
            );
            return;
        }

        finalizationRequestRef.current = true;
        finalizationActionRef.current = 'submitted';

        setSubmitDialogOpen(false);
        setFinalizationError(null);
        setFinalizing(true);

        const flushResult = await autosave.flush();

        if (countdownExpiredRef.current) {
            finalizationRequestRef.current = false;
            finalizationActionRef.current = null;
            setFinalizing(false);
            return;
        }

        if (!flushResult?.ok) {
            finalizationRequestRef.current = false;
            finalizationActionRef.current = null;
            setFinalizing(false);

            if (flushResult?.reason === 'network') {
                setFinalizationError(
                    'Jawaban terakhir belum berhasil disimpan. Periksa koneksi lalu coba selesaikan kembali.',
                );
            } else if (flushResult?.reason === 'stopped') {
                setFinalizationError(
                    'Penyimpanan jawaban terhenti. Muat ulang state lalu coba kembali.',
                );
            }

            return;
        }

        autosave.stop();

        router.post(
            finishUrl,
            {
                reason: 'submitted',
            },
            {
                preserveScroll: true,

                onSuccess: () => {
                    setFinalized(true);
                },

                onError: () => {
                    setFinalizing(false);
                    setFinalizationError(
                        'Jawaban final ditolak. Muat ulang state lalu coba kembali.',
                    );
                },

                onFinish: () => {
                    finalizationRequestRef.current = false;
                    finalizationActionRef.current = null;
                },
            },
        );
    };

    const sendTimeout = useCallback(() => {
        if (!finishUrl || finalizationRequestRef.current || sessionUnavailable) {
            return;
        }

        autosave.stop({ nextStatus: 'expired', clearQueue: true });
        setSubmitDialogOpen(false);
        setTimeoutError(null);
        setFinalizing(true);
        finalizationRequestRef.current = true;
        finalizationActionRef.current = 'timeout';

        router.post(
            finishUrl,
            { reason: 'timeout' },
            {
                preserveScroll: true,
                onSuccess: () => setFinalized(true),
                onError: () => {
                    setFinalizing(false);
                    setTimeoutError('Proses timeout ditolak. Muat ulang state atau coba lanjutkan kembali.');
                },
                onFinish: () => {
                    finalizationRequestRef.current = false;
                    finalizationActionRef.current = null;
                },
            },
        );
    }, [autosave, finishUrl, sessionUnavailable]);

    useEffect(() => {
        if (showExpiredPanel && !timeoutAutoAttemptedRef.current && !sessionUnavailable) {
            timeoutAutoAttemptedRef.current = true;
            sendTimeout();
        }
    }, [sendTimeout, sessionUnavailable, showExpiredPanel]);

    const transitionToAnswering = useCallback(() => {
        if (mode !== 'memorization' || transitioning) {
            return;
        }

        setTransitioning(true);
        setTransitionError(null);
        finalizationActionRef.current = 'memorization';

        router.visit(window.location.href, {
            method: 'get',
            replace: true,
            preserveScroll: true,
            preserveState: false,
            onError: () => setTransitionError('Fase menjawab belum dapat dimuat. Tidak ada waktu tambahan yang dibuat.'),
            onFinish: () => {
                setTransitioning(false);
                finalizationActionRef.current = null;
            },
        });
    }, [mode, transitioning]);

    useEffect(() => {
        if (
            mode === 'memorization'
            && countdown.hasValidDeadline
            && countdown.isExpired
            && !transitionAttemptedRef.current
        ) {
            transitionAttemptedRef.current = true;
            transitionToAnswering();
        }
    }, [countdown.hasValidDeadline, countdown.isExpired, mode, transitionToAnswering]);

    useEffect(() => {
        const removeInvalidListener = router.on('invalid', (event) => {
            const status = Number(event?.detail?.response?.status) || null;
            const action = finalizationActionRef.current;

            if (status === 404) {
                setSessionUnavailable(true);
                setFinalizing(false);
            } else if (status === 409) {
                if (action === 'timeout') {
                    setTimeoutError('State timeout belum dapat diselesaikan. Coba lanjutkan atau muat ulang halaman.');
                    setFinalizing(false);
                } else if (action === 'memorization') {
                    setTransitionError('State fase berubah di server. Muat ulang untuk melanjutkan.');
                } else {
                    setRuntimeConflict('Finalisasi bertabrakan dengan state server yang lebih baru. Muat ulang halaman.');
                    setFinalizing(false);
                }
            } else if (status === 422) {
                setFinalizationError('Permintaan ditolak oleh validasi. Periksa format jawaban.');
                setFinalizing(false);
            } else {
                setFinalizationError('Layanan asesmen sedang bermasalah. Tidak ada detail internal yang ditampilkan.');
                setFinalizing(false);
            }

            return false;
        });
        const removeExceptionListener = router.on('exception', () => {
            const action = finalizationActionRef.current;

            if (action === 'timeout') {
                setTimeoutError('Koneksi terputus. Jawaban tetap dikunci; gunakan tombol Coba Lanjutkan.');
            } else if (action === 'memorization') {
                setTransitionError('Koneksi terputus saat memuat fase menjawab. Silakan coba lagi.');
            } else {
                setFinalizationError('Koneksi terputus. Periksa jaringan lalu coba lagi.');
            }

            setFinalizing(false);
            return false;
        });

        return () => {
            removeInvalidListener();
            removeExceptionListener();
        };
    }, []);

    const title = `${subtest?.code ?? 'Subtes'} — ${subtest?.name ?? 'Pengerjaan Subtes'}`;

    return (
        <PublicLayout>
            <Head title={`Tes Kemampuan Kognitif Adaptasi${subtest?.code ? ` — ${subtest.code}` : ''}`} />

            <div className="sr-only" aria-live="polite">{timerAnnouncement}</div>

            <div className="mx-auto w-full max-w-7xl flex-1 px-4 py-5 sm:px-6 sm:py-8">
                <header className="sticky top-3 z-30 mb-5 rounded-2xl border border-zinc-800 bg-zinc-900/95 p-4 shadow-xl backdrop-blur-xl">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div className="min-w-0">
                            <p className="text-xs font-mono uppercase tracking-widest text-blue-400">
                                Subtes {subtest?.sequence ?? '-'} dari 9
                            </p>
                            <h1 className="mt-1 truncate text-lg font-bold text-white sm:text-xl">{title}</h1>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {mode === 'answering' && (
                                <IstAutosaveStatus
                                    status={displayedAutosaveStatus}
                                    lastSavedAt={autosave.lastSavedAt}
                                    onRetry={() => void autosave.retry()}
                                    onReload={reloadCanonicalState}
                                />
                            )}
                            <IstCountdown
                                {...countdown}
                                label={mode === 'memorization' ? 'Sisa waktu menghafal' : 'Sisa waktu menjawab'}
                            />
                        </div>
                    </div>

                    <div className="mt-4 border-t border-zinc-800 pt-4">
                        <IstSubtestProgress currentSequence={subtest?.sequence} />
                    </div>
                </header>

                {(runtimeConflict || sessionUnavailable || finalizationError || !countdown.hasValidDeadline) && (
                    <div ref={errorNoticeRef} tabIndex={-1} className="mb-5 focus:outline-none">
                        <IstStateNotice tone="error" title={sessionUnavailable ? 'Sesi tidak tersedia' : 'Pengerjaan dikunci sementara'}>
                            <p>
                                {sessionUnavailable
                                    ? 'Sesi tidak ditemukan atau aksesnya sudah tidak berlaku.'
                                    : runtimeConflict
                                      ?? finalizationError
                                      ?? 'Deadline server tidak valid. Muat ulang halaman untuk mengambil state canonical.'}
                            </p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {!sessionUnavailable && (
                                    <button
                                        type="button"
                                        onClick={reloadCanonicalState}
                                        className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-300/30 px-4 font-semibold hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                    >
                                        <RefreshCw className="h-4 w-4" aria-hidden="true" />
                                        Muat Ulang
                                    </button>
                                )}
                                {sessionUnavailable && (
                                    <Link
                                        href={route('ist.index')}
                                        className="inline-flex min-h-11 items-center rounded-lg border border-red-300/30 px-4 font-semibold hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                    >
                                        Kembali ke Halaman Asesmen
                                    </Link>
                                )}
                            </div>
                        </IstStateNotice>
                    </div>
                )}

                {mode === 'memorization' && (
                    <IstMemorizationPanel
                        groups={memorizationGroups}
                        countdown={countdown}
                        locked={countdown.isExpired}
                        transitioning={transitioning}
                        transitionError={transitionError}
                        onRetry={() => {
                            transitionAttemptedRef.current = false;
                            transitionToAnswering();
                        }}
                    />
                )}

                {showExpiredPanel && (
                    <IstExpiredPanel
                        onContinue={sendTimeout}
                        processing={finalizing}
                        error={timeoutError}
                    />
                )}

                {mode === 'answering' && !showExpiredPanel && (
                    <>
                        <div className="mb-5 grid grid-cols-2 gap-3 sm:max-w-sm">
                            <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3">
                                <p className="text-xs text-emerald-200">Dijawab</p>
                                <p className="mt-1 text-xl font-bold text-white">{answeredCount}</p>
                            </div>
                            <div className="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3">
                                <p className="text-xs text-amber-200">Kosong</p>
                                <p className="mt-1 text-xl font-bold text-white">{blankCount}</p>
                            </div>
                        </div>

                        {safeQuestions.length === 0 ? (
                            <IstStateNotice tone="error" title="Soal belum tersedia">
                                Tidak ada soal yang dapat ditampilkan untuk subtes ini. Tidak ada jawaban fallback yang dibuat.
                            </IstStateNotice>
                        ) : (
                            <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                                <main>
                                <IstQuestionCard
                                    question={currentQuestion}
                                    answer={answers[currentQuestion?.id]}
                                    onChange={handleAnswerChange}
                                    disabled={inputLocked}
                                    disabledReason={lockReason}
                                    subtestCode={subtest?.code}
                                />

                                    <div className="mt-5 flex flex-col gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <button
                                            type="button"
                                            onClick={() => setCurrentIndex((index) => Math.max(0, index - 1))}
                                            disabled={currentIndex === 0}
                                            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-semibold text-zinc-300 hover:border-zinc-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                        >
                                            <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                                            Sebelumnya
                                        </button>

                                        <span className="text-center text-xs font-mono text-zinc-500">
                                            Soal {currentIndex + 1} dari {safeQuestions.length}
                                        </span>

                                        {currentIndex < safeQuestions.length - 1 ? (
                                            <button
                                                type="button"
                                                onClick={() => setCurrentIndex((index) => Math.min(safeQuestions.length - 1, index + 1))}
                                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                            >
                                                Berikutnya
                                                <ArrowRight className="h-4 w-4" aria-hidden="true" />
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => setSubmitDialogOpen(true)}
                                                disabled={inputLocked}
                                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 text-sm font-semibold text-white hover:from-blue-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                            >
                                                <Flag className="h-4 w-4" aria-hidden="true" />
                                                Selesaikan Subtes
                                            </button>
                                        )}
                                    </div>

                                    {currentIndex < safeQuestions.length - 1 && (
                                        <div className="mt-3 flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() => setSubmitDialogOpen(true)}
                                                disabled={inputLocked}
                                                className="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-xs font-semibold text-zinc-400 hover:bg-zinc-900 hover:text-white disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                            >
                                                <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                                Selesai lebih awal
                                            </button>
                                        </div>
                                    )}
                                </main>

                                <IstQuestionNavigator
                                    questions={safeQuestions}
                                    activeIndex={currentIndex}
                                    answeredQuestionIds={answeredQuestionIds}
                                    onSelect={setCurrentIndex}
                                />
                            </div>
                        )}
                    </>
                )}

                {!['memorization', 'answering', 'expired'].includes(mode) && (
                    <IstStateNotice tone="error" title="Mode subtes tidak dikenali">
                        Halaman tidak dapat menentukan state pengerjaan yang aman.
                    </IstStateNotice>
                )}
            </div>

            <IstSubmitDialog
                open={submitDialogOpen}
                answeredCount={answeredCount}
                blankCount={blankCount}
                onClose={() => setSubmitDialogOpen(false)}
                onConfirm={handleManualSubmit}
                processing={finalizing}
            />
        </PublicLayout>
    );
}
