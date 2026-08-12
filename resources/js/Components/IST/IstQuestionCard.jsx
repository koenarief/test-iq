import { AlertCircle, CheckCircle2 } from 'lucide-react';
import IstImageViewer from '@/Components/IST/IstImageViewer';
import { faQuestionOptionImage } from '@/Support/IST/faVisuals';
import {
    WU_QUESTION_PROMPT,
    wuMasterImage,
    wuQuestionTargetImage,
} from '@/Support/IST/wuVisuals';

const choiceTypes = new Set(['single_choice', 'single_choice_weighted', 'image_choice']);

function optionLabel(option, index) {
    return option?.text?.trim() || `Pilihan ${option?.optionKey ?? index + 1}`;
}

export default function IstQuestionCard({
    question,
    answer,
    onChange,
    disabled = false,
    disabledReason = null,
    subtestCode = null,
}) {
    if (!question) {
        return (
            <div className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-6 text-sm text-zinc-400">
                Soal tidak tersedia.
            </div>
        );
    }

    const answerType = question.answerType;
    const subtestCodeNormalized = String(subtestCode ?? '').toUpperCase();
    const isFa = subtestCodeNormalized === 'FA';
    const isWu = subtestCodeNormalized === 'WU';
    const usesFixedVisualMasters = isFa || isWu;
    const displayedPrompt = isWu ? WU_QUESTION_PROMPT : question.prompt;
    const displayedQuestionImage = isWu
        ? wuQuestionTargetImage(question.displayOrder)
        : question.image;
    const options = Array.isArray(question.options) ? question.options : [];
    const isChoice = choiceTypes.has(answerType);
    const isNumeric = answerType === 'numeric';
    const answered = isNumeric
        ? String(answer?.numericAnswer ?? '').trim() !== ''
        : Boolean(answer?.selectedOptionKey);
    const fieldName = `ist-question-${question.id}`;

    return (
        <article className="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-5 shadow-2xl sm:p-8">
            <header className="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-zinc-800 pb-4">
                <div className="flex items-center gap-3">
                    <span className="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-blue-500/30 bg-blue-500/10 px-2 font-mono text-sm font-bold text-blue-300">
                        #{question.displayOrder ?? '-'}
                    </span>
                    <h1 className="text-lg font-bold text-white">Pengerjaan Subtes</h1>
                </div>

                <span
                    className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium ${
                        answered
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                            : 'border-amber-500/30 bg-amber-500/10 text-amber-300'
                    }`}
                >
                    {answered ? (
                        <CheckCircle2 className="h-3.5 w-3.5" aria-hidden="true" />
                    ) : (
                        <AlertCircle className="h-3.5 w-3.5" aria-hidden="true" />
                    )}
                    {answered ? 'Sudah dijawab' : 'Belum dijawab'}
                </span>
            </header>

            <div className="space-y-6">
                {disabled && disabledReason && (
                    <p className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-100" role="status">
                        {disabledReason}
                    </p>
                )}

                {displayedPrompt ? (
                    <p className="whitespace-pre-line text-base leading-relaxed text-zinc-100 sm:text-lg">
                        {displayedPrompt}
                    </p>
                ) : (
                    <p className="text-sm text-zinc-500">Teks soal tidak tersedia.</p>
                )}

                <IstImageViewer image={displayedQuestionImage} fallbackAlt="Ilustrasi soal" />

                {isChoice && (
                    <fieldset>
                        <legend className="mb-3 text-sm font-semibold text-zinc-200">
                            {isFa
                                ? 'Pilih bentuk jawaban A–E'
                                : isWu
                                  ? 'Pilih kubus acuan A–E'
                                  : 'Pilih satu jawaban'}
                        </legend>

                        {options.length > 0 ? (
                            <div
                            className={
                                usesFixedVisualMasters
                                    ? 'grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5'
                                    : answerType === 'image_choice'
                                      ? 'grid grid-cols-1 gap-3 sm:grid-cols-2'
                                      : 'space-y-3'
                            }
                        >
                                {options.map((option, index) => {
                                    const optionKey = option?.optionKey ?? String(index + 1);
                                    const inputId = `${fieldName}-${optionKey}`;
                                    const selected = answer?.selectedOptionKey === optionKey;

                                    return (
                                        <div
                                            key={optionKey}
                                            className={`rounded-xl border p-3 ${
                                                selected
                                                    ? 'border-blue-400/70 bg-blue-500/10'
                                                    : 'border-zinc-800 bg-zinc-950/60 hover:border-zinc-700'
                                            }`}
                                        >
                                            <input
                                                id={inputId}
                                                type="radio"
                                                name={fieldName}
                                                value={optionKey}
                                                checked={selected}
                                                disabled={disabled}
                                                onChange={() => onChange?.({
                                                    selectedOptionKey: optionKey,
                                                    numericAnswer: null,
                                                })}
                                                className="peer sr-only disabled:cursor-not-allowed"
                                            />
                                            <label
                                                htmlFor={inputId}
                                                className={`flex min-h-11 cursor-pointer gap-3 rounded-lg p-2 text-sm text-zinc-200 focus-within:outline-none peer-disabled:cursor-not-allowed peer-disabled:opacity-60 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400 ${
                                                    usesFixedVisualMasters ? 'items-center justify-center' : 'items-start'
                                                }`}
                                            >
                                                <span
                                                    className={`mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border ${
                                                        selected
                                                            ? 'border-blue-400 bg-blue-500 text-white'
                                                            : 'border-zinc-600 bg-zinc-900 text-transparent'
                                                    }`}
                                                    aria-hidden="true"
                                                >
                                                    <span className="h-2 w-2 rounded-full bg-current" />
                                                </span>
                                                <span className={usesFixedVisualMasters ? 'text-base font-bold text-blue-200' : 'leading-relaxed'}>
                                                    {usesFixedVisualMasters ? optionKey : optionLabel(option, index)}
                                                </span>
                                            </label>

                                            <IstImageViewer
                                                image={isFa
                                                    ? faQuestionOptionImage(question.displayOrder, optionKey)
                                                    : isWu
                                                      ? wuMasterImage(optionKey)
                                                      : option?.image}
                                                fallbackAlt={isFa
                                                    ? `Pilihan bentuk FA ${optionKey}`
                                                    : isWu
                                                      ? `Kubus acuan ${optionKey}`
                                                      : `Ilustrasi pilihan ${optionKey}`}
                                                className={usesFixedVisualMasters
                                                    ? 'mt-2 [&>div]:min-h-24'
                                                    : 'mt-2'}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-200" role="alert">
                                Opsi jawaban belum tersedia.
                            </p>
                        )}

                        {answer?.selectedOptionKey && (
                            <button
                                type="button"
                                onClick={() => onChange?.({ selectedOptionKey: null, numericAnswer: null })}
                                disabled={disabled}
                                className="mt-3 min-h-11 rounded-lg px-3 text-xs font-semibold text-zinc-400 hover:bg-zinc-800 hover:text-white disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                            >
                                Kosongkan jawaban
                            </button>
                        )}
                    </fieldset>
                )}

                {isNumeric && (
                    <div>
                        <label htmlFor={`${fieldName}-numeric`} className="mb-2 block text-sm font-semibold text-zinc-200">
                            Jawaban numerik
                        </label>
                        <input
                            id={`${fieldName}-numeric`}
                            type="text"
                            inputMode="decimal"
                            autoComplete="off"
                            disabled={disabled}
                            value={answer?.numericAnswer ?? ''}
                            onChange={(event) => onChange?.({
                                selectedOptionKey: null,
                                numericAnswer: event.target.value,
                            })}
                            placeholder="Masukkan angka"
                            aria-invalid={Boolean(answer?.validationError)}
                            aria-describedby={answer?.validationError ? `${fieldName}-numeric-error` : undefined}
                            className="min-h-12 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-base text-white placeholder:text-zinc-600 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:cursor-not-allowed disabled:opacity-60"
                        />
                        {answer?.validationError && (
                            <p id={`${fieldName}-numeric-error`} className="mt-2 text-xs text-red-400" role="alert">
                                {answer.validationError}
                            </p>
                        )}
                    </div>
                )}

                {!isChoice && !isNumeric && (
                    <p className="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200" role="alert">
                        Jenis jawaban soal ini belum didukung.
                    </p>
                )}
            </div>
        </article>
    );
}
