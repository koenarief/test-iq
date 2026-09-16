import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion, AnimatePresence } from 'motion/react';
import {
    Clock,
    ArrowRight,
    ArrowLeft,
    CheckCircle2,
    AlertCircle,
    Grid,
    HelpCircle,
    Tag,
} from 'lucide-react';

export default function Test({ competencyTest, questions = [], durationMinutes = 30 }) {
    const totalSeconds = durationMinutes * 60;

    const [currentIndex, setCurrentIndex] = useState(0);
    const [answers, setAnswers] = useState({});
    const [showNavigator, setShowNavigator] = useState(false);
    const [processing, setProcessing] = useState(false);

    /*
     * Ref ini dipakai agar:
     * 1. submit tidak terkirim dua kali;
     * 2. timeout selalu mengambil jawaban paling terbaru,
     *    termasuk jawaban yang baru saja diklik.
     */
    const submitGuardRef = useRef(false);
    const answersRef = useRef({});

    const calculateRemainingSeconds = () => {
        if (!competencyTest.started_at) {
            return totalSeconds;
        }

        let dateStr = competencyTest.started_at;
        if (dateStr && !dateStr.includes('T')) {
            dateStr = dateStr.replace(' ', 'T');
        }
        if (dateStr && !dateStr.endsWith('Z') && !dateStr.includes('+')) {
            dateStr += 'Z';
        }

        const startTime = new Date(dateStr).getTime();
        const now = Date.now();

        if (Number.isNaN(startTime)) {
            return totalSeconds;
        }

        const elapsedSeconds = Math.floor((now - startTime) / 1000);

        return Math.max(0, totalSeconds - elapsedSeconds);
    };

    const [timeLeft, setTimeLeft] = useState(calculateRemainingSeconds);

    const formatTime = (seconds) => {
        const safeSeconds = Math.max(0, Number(seconds) || 0);
        const mins = Math.floor(safeSeconds / 60);
        const secs = safeSeconds % 60;

        return `${mins.toString().padStart(2, '0')}:${secs
            .toString()
            .padStart(2, '0')}`;
    };

    const currentQuestion = questions[currentIndex] || {};
    const currentAnswer = answers[currentQuestion.id] ?? null;

    const testLocked = timeLeft <= 0 || processing || submitGuardRef.current;

    const handleSelectOption = (label) => {
        if (testLocked || !currentQuestion?.id) {
            return;
        }

        const questionId = currentQuestion.id;

        const nextAnswers = {
            ...answersRef.current,
            [questionId]: label,
        };

        answersRef.current = nextAnswers;
        setAnswers(nextAnswers);
    };

    const isCurrentQuestionAnswered = Boolean(currentAnswer);

    const totalAnswered = Object.values(answers).filter(Boolean).length;

    const isAllAnswered =
        questions.length > 0 && totalAnswered === questions.length;

    const submitTest = (reason = 'submitted') => {
        if (submitGuardRef.current || processing) {
            return;
        }

        const currentAnswers = answersRef.current;

        if (reason === 'submitted') {
            const completeCount = Object.values(currentAnswers).filter(
                Boolean,
            ).length;

            if (
                questions.length === 0 ||
                completeCount !== questions.length
            ) {
                return;
            }
        }

        submitGuardRef.current = true;
        setProcessing(true);
        setShowNavigator(false);

        router.post(
            route('competency.submit', competencyTest.id),
            {
                answers: currentAnswers,
                reason,
            },
            {
                preserveScroll: true,

                onError: () => {
                    submitGuardRef.current = false;
                    setProcessing(false);
                },

                onFinish: () => {
                    setProcessing(false);
                    submitGuardRef.current = false;
                },
            },
        );
    };

    const handleSubmitTest = () => {
        submitTest('submitted');
    };

    useEffect(() => {
        let timer;

        const tick = () => {
            const remaining = calculateRemainingSeconds();

            setTimeLeft(remaining);

            if (remaining <= 0 && !submitGuardRef.current) {
                if (timer) {
                    window.clearInterval(timer);
                }
                submitTest('timeout');
            }
        };

        tick();

        timer = window.setInterval(tick, 1000);

        return () => {
            if (timer) {
                window.clearInterval(timer);
            }
        };

        // Timer sengaja menggunakan started_at dari instance test ini.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <PublicLayout>
            <Head title={`Tes Kompetensi - Soal #${currentIndex + 1}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8 flex-1 flex flex-col">
                {/* Sticky Top Header Bar */}
                <div className="sticky top-4 z-30 bg-zinc-900/90 border border-zinc-800 rounded-2xl p-4 mb-6 backdrop-blur-xl shadow-xl flex flex-wrap items-center justify-between gap-4">
                    {/* Participant & Progress */}
                    <div className="flex items-center gap-4">
                        <div className="hidden sm:block">
                            <span className="text-xs text-zinc-500 font-mono block">
                                Peserta:
                            </span>
                            <span className="text-sm font-semibold text-white">
                                {competencyTest.participant_name}
                            </span>
                        </div>

                        <div className="h-8 w-px bg-zinc-800 hidden sm:block" />

                        <div>
                            <span className="text-xs text-zinc-400 font-mono">
                                Progress Soal:{' '}
                                <strong className="text-white">
                                    {currentIndex + 1}
                                </strong>{' '}
                                / {questions.length}
                            </span>

                            <div className="w-36 sm:w-48 h-2 bg-zinc-950 rounded-full overflow-hidden mt-1 border border-zinc-800">
                                <div
                                    className="h-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-300"
                                    style={{
                                        width: `${
                                            ((currentIndex + 1) /
                                                (questions.length || 1)) *
                                            100
                                        }%`,
                                    }}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Timer & Navigator */}
                    <div className="flex items-center gap-3">
                        <div
                            className={`flex items-center gap-2 px-3.5 py-2 rounded-xl border text-sm font-mono font-semibold ${
                                timeLeft <= 0
                                    ? 'bg-red-500/15 border-red-500/40 text-red-400'
                                    : timeLeft < 300
                                      ? 'bg-red-500/10 border-red-500/30 text-red-400 animate-pulse'
                                      : 'bg-zinc-950/80 border-zinc-800 text-zinc-200'
                            }`}
                        >
                            <Clock className="w-4 h-4 text-blue-400" />
                            <span>{formatTime(timeLeft)}</span>
                        </div>

                        <button
                            type="button"
                            onClick={() => {
                                if (!testLocked) {
                                    setShowNavigator(!showNavigator);
                                }
                            }}
                            disabled={testLocked}
                            className="p-2.5 rounded-xl bg-zinc-950/80 border border-zinc-800 text-zinc-300 hover:text-white hover:border-zinc-700 transition-colors flex items-center gap-2 text-xs font-mono disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <Grid className="w-4 h-4 text-indigo-400" />
                            <span className="hidden md:inline">
                                Navigasi Soal
                            </span>
                            <span className="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-400 text-xs">
                                {totalAnswered}/{questions.length}
                            </span>
                        </button>
                    </div>
                </div>

                {/* Status Timeout */}
                {timeLeft <= 0 && (
                    <div className="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300 flex items-center gap-3">
                        <AlertCircle className="w-5 h-5 shrink-0" />
                        <span>
                            Waktu pengerjaan telah habis. Jawaban sedang
                            disimpan dan tes akan diselesaikan otomatis.
                        </span>
                    </div>
                )}

                {/* Main Content */}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-1 items-start">
                    {/* Question Card */}
                    <div className="lg:col-span-3">
                        <AnimatePresence mode="wait">
                            <motion.div
                                key={currentIndex}
                                initial={{ opacity: 0, x: 20 }}
                                animate={{ opacity: 1, x: 0 }}
                                exit={{ opacity: 0, x: -20 }}
                                transition={{ duration: 0.25 }}
                                className="bg-zinc-900/90 border border-zinc-800 rounded-2xl p-5 sm:p-8 shadow-2xl backdrop-blur-xl"
                            >
                                {/* Question Header */}
                                <div className="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-zinc-800 mb-6">
                                    <div className="flex items-center gap-3">
                                        <span className="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/30 text-blue-400 flex items-center justify-center font-mono font-bold text-sm shrink-0">
                                            #{currentIndex + 1}
                                        </span>

                                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-mono">
                                            <Tag className="w-3.5 h-3.5" />
                                            {currentQuestion.category_name}
                                        </span>
                                    </div>

                                    {isCurrentQuestionAnswered ? (
                                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono">
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                            Sudah Terisi
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-mono">
                                            <AlertCircle className="w-3.5 h-3.5" />
                                            Belum Terisi
                                        </span>
                                    )}
                                </div>

                                {/* Question Text */}
                                <p className="text-sm sm:text-base text-zinc-100 leading-relaxed font-medium mb-6">
                                    {currentQuestion.question_text}
                                </p>

                                {/* Options */}
                                <div className="space-y-3 mb-8">
                                    {(currentQuestion.options || []).map(
                                        (option) => {
                                            const isSelected =
                                                currentAnswer === option.label;

                                            return (
                                                <button
                                                    key={option.label}
                                                    type="button"
                                                    disabled={testLocked}
                                                    onClick={() =>
                                                        handleSelectOption(
                                                            option.label,
                                                        )
                                                    }
                                                    className={`w-full text-left p-4 rounded-xl border transition-all duration-200 flex items-start gap-3 disabled:cursor-not-allowed ${
                                                        isSelected
                                                            ? 'bg-blue-500/10 border-blue-500/50 shadow-lg shadow-blue-500/5'
                                                            : 'bg-zinc-950/40 border-zinc-800/80 hover:border-zinc-700'
                                                    }`}
                                                >
                                                    <span
                                                        className={`w-6 h-6 rounded-md text-xs font-mono flex items-center justify-center shrink-0 mt-0.5 border ${
                                                            isSelected
                                                                ? 'bg-blue-600 text-white border-blue-400'
                                                                : 'bg-zinc-800 text-zinc-400 border-zinc-700'
                                                        }`}
                                                    >
                                                        {option.label}
                                                    </span>
                                                    <p
                                                        className={`text-sm leading-relaxed ${
                                                            isSelected
                                                                ? 'text-white font-medium'
                                                                : 'text-zinc-300'
                                                        }`}
                                                    >
                                                        {option.text}
                                                    </p>
                                                </button>
                                            );
                                        },
                                    )}
                                </div>

                                {/* Guidance */}
                                <div className="p-3.5 rounded-xl bg-zinc-950/60 border border-zinc-800/80 mb-8 flex items-center gap-3 text-xs text-zinc-400">
                                    <HelpCircle className="w-4 h-4 text-blue-400 shrink-0" />
                                    <span>
                                        Pilih <strong>1 jawaban</strong> yang
                                        paling menggambarkan tindakan Anda
                                        pada situasi tersebut.
                                    </span>
                                </div>

                                {/* Footer */}
                                <div className="flex items-center justify-between gap-4 pt-4 border-t border-zinc-800">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setCurrentIndex((prev) =>
                                                Math.max(0, prev - 1),
                                            )
                                        }
                                        disabled={
                                            currentIndex === 0 || testLocked
                                        }
                                        className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-zinc-800/60 hover:bg-zinc-800 text-zinc-300 text-xs font-semibold disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                    >
                                        <ArrowLeft className="w-4 h-4" />
                                        <span>Sebelumnya</span>
                                    </button>

                                    {currentIndex < questions.length - 1 ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setCurrentIndex((prev) =>
                                                    Math.min(
                                                        questions.length - 1,
                                                        prev + 1,
                                                    ),
                                                )
                                            }
                                            disabled={
                                                !isCurrentQuestionAnswered ||
                                                testLocked
                                            }
                                            className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-lg shadow-blue-600/25 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-200"
                                        >
                                            <span>Selanjutnya</span>
                                            <ArrowRight className="w-4 h-4" />
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={handleSubmitTest}
                                            disabled={
                                                !isAllAnswered ||
                                                processing ||
                                                timeLeft <= 0 ||
                                                submitGuardRef.current
                                            }
                                            className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-semibold shadow-lg shadow-emerald-600/25 disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-200"
                                        >
                                            <CheckCircle2 className="w-4 h-4" />
                                            <span>
                                                {processing
                                                    ? 'Menyimpan...'
                                                    : 'Submit Jawaban'}
                                            </span>
                                        </button>
                                    )}
                                </div>
                            </motion.div>
                        </AnimatePresence>
                    </div>

                    {/* Sidebar Navigator */}
                    <div
                        className={`lg:block ${
                            showNavigator ? 'block' : 'hidden'
                        } bg-zinc-900/90 border border-zinc-800 rounded-2xl p-5 shadow-2xl backdrop-blur-xl`}
                    >
                        <h4 className="text-xs font-mono font-semibold text-zinc-300 uppercase tracking-wider mb-4 flex items-center justify-between">
                            <span>Daftar {questions.length} Soal</span>
                            <span className="text-blue-400">
                                {totalAnswered}/{questions.length}
                            </span>
                        </h4>

                        <div className="grid grid-cols-6 lg:grid-cols-4 gap-2">
                            {questions.map((question, idx) => {
                                const answered = Boolean(
                                    answers[question.id],
                                );
                                const current = idx === currentIndex;

                                return (
                                    <button
                                        key={question.id || idx}
                                        type="button"
                                        disabled={testLocked}
                                        onClick={() => {
                                            if (testLocked) {
                                                return;
                                            }

                                            setCurrentIndex(idx);
                                            setShowNavigator(false);
                                        }}
                                        className={`h-10 rounded-xl font-mono text-xs font-bold transition-all flex items-center justify-center border disabled:cursor-not-allowed ${
                                            current
                                                ? 'bg-blue-600 text-white border-blue-400 ring-4 ring-blue-500/20 scale-105'
                                                : answered
                                                  ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20'
                                                  : 'bg-zinc-950/60 border-zinc-800 text-zinc-500 hover:text-white hover:border-zinc-700'
                                        }`}
                                    >
                                        {idx + 1}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
