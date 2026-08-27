import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';

const ANSWER_TYPE_LABELS = {
    single_choice: 'Pilihan Tunggal',
    single_choice_weighted: 'Pilihan Tunggal Berbobot (GE, skor 0-3)',
    numeric: 'Numerik',
    image_choice: 'Pilihan Gambar',
};

const KIND_LABELS = {
    scored: 'Soal Utama (dinilai)',
    example: 'Soal Contoh',
};

function blankOptions(optionKeys) {
    return optionKeys.map((key) => ({
        option_key: key,
        option_text: '',
        image_disk: '',
        image_path: '',
        image_alt: '',
        is_correct: false,
        score_value: 0,
    }));
}

export default function QuestionForm({
    subtests,
    answerTypes,
    difficulties,
    kinds,
    optionKeys,
    question,
    submitUrl,
    method = 'post',
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        ist_subtest_id: question?.ist_subtest_id ?? subtests[0]?.id ?? '',
        kind: question?.kind ?? kinds[0] ?? 'scored',
        question_number: question?.question_number ?? '',
        display_order: question?.display_order ?? '',
        answer_type:
            question?.answer_type ??
            subtests[0]?.default_answer_type ??
            answerTypes[0],
        prompt: question?.prompt ?? '',
        image_disk: question?.image_disk ?? '',
        image_path: question?.image_path ?? '',
        image_alt: question?.image_alt ?? '',
        example_explanation: question?.example_explanation ?? '',
        numeric_answer_key: question?.numeric_answer_key ?? '',
        max_score: question?.max_score ?? 1,
        difficulty: question?.difficulty ?? 'medium',
        is_active: question?.is_active ?? true,
        options:
            question?.options?.length > 0
                ? question.options.map((option) => ({
                      option_key: option.option_key,
                      option_text: option.option_text ?? '',
                      image_disk: option.image_disk ?? '',
                      image_path: option.image_path ?? '',
                      image_alt: option.image_alt ?? '',
                      is_correct: option.is_correct,
                      score_value: option.score_value,
                  }))
                : blankOptions(optionKeys),
    });

    const isNumeric = data.answer_type === 'numeric';
    const isWeighted = data.answer_type === 'single_choice_weighted';

    const submit = (e) => {
        e.preventDefault();

        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    const handleAnswerTypeChange = (nextType) => {
        setData((current) => ({
            ...current,
            answer_type: nextType,
            options:
                nextType === 'numeric'
                    ? []
                    : current.options.length > 0
                      ? current.options
                      : blankOptions(optionKeys),
        }));
    };

    const setOptionField = (index, field, value) => {
        setData(
            'options',
            data.options.map((option, i) =>
                i === index ? { ...option, [field]: value } : option,
            ),
        );
    };

    const markCorrect = (index) => {
        setData(
            'options',
            data.options.map((option, i) => ({
                ...option,
                is_correct: i === index,
                score_value: i === index ? 1 : 0,
            })),
        );
    };

    const setWeightedScore = (index, rawScore) => {
        const score = Number(rawScore);

        setData(
            'options',
            data.options.map((option, i) =>
                i === index
                    ? { ...option, score_value: score, is_correct: score === 3 }
                    : option,
            ),
        );
    };

    const optionErrors = Object.keys(errors).filter((key) =>
        key.startsWith('options'),
    );

    return (
        <form onSubmit={submit} className="space-y-8">
            <div className="grid gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="ist_subtest_id" value="Subtes" />
                    <select
                        id="ist_subtest_id"
                        value={data.ist_subtest_id}
                        onChange={(e) =>
                            setData('ist_subtest_id', Number(e.target.value))
                        }
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        {subtests.map((subtest) => (
                            <option key={subtest.id} value={subtest.id}>
                                {subtest.code} — {subtest.name}
                            </option>
                        ))}
                    </select>
                    <InputError
                        message={errors.ist_subtest_id}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="kind" value="Jenis Soal" />
                    <select
                        id="kind"
                        value={data.kind}
                        onChange={(e) => setData('kind', e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        {kinds.map((kind) => (
                            <option key={kind} value={kind}>
                                {KIND_LABELS[kind] ?? kind}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.kind} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="question_number"
                        value="Nomor Soal"
                    />
                    <TextInput
                        id="question_number"
                        type="number"
                        min="1"
                        value={data.question_number}
                        onChange={(e) =>
                            setData('question_number', e.target.value)
                        }
                        className="mt-1 block w-full"
                    />
                    <InputError
                        message={errors.question_number}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="display_order" value="Urutan Tampil" />
                    <TextInput
                        id="display_order"
                        type="number"
                        min="1"
                        value={data.display_order}
                        onChange={(e) =>
                            setData('display_order', e.target.value)
                        }
                        className="mt-1 block w-full"
                    />
                    <InputError
                        message={errors.display_order}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="answer_type" value="Tipe Jawaban" />
                    <select
                        id="answer_type"
                        value={data.answer_type}
                        onChange={(e) =>
                            handleAnswerTypeChange(e.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        {answerTypes.map((type) => (
                            <option key={type} value={type}>
                                {ANSWER_TYPE_LABELS[type] ?? type}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.answer_type} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="difficulty" value="Tingkat Kesulitan" />
                    <select
                        id="difficulty"
                        value={data.difficulty}
                        onChange={(e) => setData('difficulty', e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        {difficulties.map((level) => (
                            <option key={level} value={level}>
                                {level}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.difficulty} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="max_score" value="Skor Maksimum" />
                    <TextInput
                        id="max_score"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.max_score}
                        onChange={(e) => setData('max_score', e.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.max_score} className="mt-2" />
                </div>

                <div className="flex items-center gap-2 pt-6">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                    />
                    <InputLabel htmlFor="is_active" value="Aktif" />
                </div>
            </div>

            <div>
                <InputLabel htmlFor="prompt" value="Teks Soal" />
                <textarea
                    id="prompt"
                    value={data.prompt}
                    onChange={(e) => setData('prompt', e.target.value)}
                    rows={4}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError message={errors.prompt} className="mt-2" />
            </div>

            {data.kind === 'example' && (
                <div>
                    <InputLabel
                        htmlFor="example_explanation"
                        value="Penjelasan Contoh"
                    />
                    <textarea
                        id="example_explanation"
                        value={data.example_explanation}
                        onChange={(e) =>
                            setData('example_explanation', e.target.value)
                        }
                        rows={3}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError
                        message={errors.example_explanation}
                        className="mt-2"
                    />
                </div>
            )}

            <div className="grid gap-6 sm:grid-cols-3">
                <div>
                    <InputLabel htmlFor="image_disk" value="Disk Gambar Soal" />
                    <TextInput
                        id="image_disk"
                        value={data.image_disk}
                        onChange={(e) => setData('image_disk', e.target.value)}
                        className="mt-1 block w-full"
                        placeholder="public"
                    />
                    <InputError message={errors.image_disk} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor="image_path" value="Path Gambar Soal" />
                    <TextInput
                        id="image_path"
                        value={data.image_path}
                        onChange={(e) => setData('image_path', e.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.image_path} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor="image_alt" value="Alt Gambar Soal" />
                    <TextInput
                        id="image_alt"
                        value={data.image_alt}
                        onChange={(e) => setData('image_alt', e.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.image_alt} className="mt-2" />
                </div>
            </div>

            {isNumeric ? (
                <div>
                    <InputLabel
                        htmlFor="numeric_answer_key"
                        value="Kunci Jawaban Numerik"
                    />
                    <TextInput
                        id="numeric_answer_key"
                        value={data.numeric_answer_key}
                        onChange={(e) =>
                            setData('numeric_answer_key', e.target.value)
                        }
                        className="mt-1 block w-full max-w-xs"
                        placeholder="contoh: 27 atau -1.5"
                    />
                    <InputError
                        message={errors.numeric_answer_key}
                        className="mt-2"
                    />
                </div>
            ) : (
                <div>
                    <h3 className="text-sm font-medium text-gray-700">
                        Opsi Jawaban
                    </h3>
                    <p className="mt-1 text-xs text-gray-500">
                        {isWeighted
                            ? 'Isi skor 0-3 tiap opsi. Tepat satu opsi harus bernilai 3 (jawaban benar).'
                            : 'Pilih tepat satu opsi sebagai jawaban benar.'}
                    </p>

                    <div className="mt-3 space-y-3">
                        {data.options.map((option, index) => (
                            <div
                                key={option.option_key}
                                className="grid grid-cols-12 items-start gap-3 rounded-md border border-gray-200 p-3"
                            >
                                <div className="col-span-1 pt-2 text-center font-semibold text-gray-500">
                                    {option.option_key}
                                </div>
                                <div className="col-span-5">
                                    <TextInput
                                        value={option.option_text}
                                        onChange={(e) =>
                                            setOptionField(
                                                index,
                                                'option_text',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Teks opsi"
                                        className="block w-full"
                                    />
                                </div>
                                <div className="col-span-3">
                                    <TextInput
                                        value={option.image_path}
                                        onChange={(e) =>
                                            setOptionField(
                                                index,
                                                'image_path',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Path gambar (opsional)"
                                        className="block w-full"
                                    />
                                </div>
                                <div className="col-span-3 flex items-center justify-end gap-3">
                                    {isWeighted ? (
                                        <TextInput
                                            type="number"
                                            min="0"
                                            max="3"
                                            value={option.score_value}
                                            onChange={(e) =>
                                                setWeightedScore(
                                                    index,
                                                    e.target.value,
                                                )
                                            }
                                            className="w-20"
                                        />
                                    ) : (
                                        <label className="flex items-center gap-2 text-sm text-gray-700">
                                            <input
                                                type="radio"
                                                name="correct_option"
                                                checked={option.is_correct}
                                                onChange={() =>
                                                    markCorrect(index)
                                                }
                                                className="text-indigo-600 focus:ring-indigo-500"
                                            />
                                            Benar
                                        </label>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    {optionErrors.map((key) => (
                        <InputError
                            key={key}
                            message={errors[key]}
                            className="mt-2"
                        />
                    ))}
                </div>
            )}

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                <Link href={route('admin.ist-questions.index')}>
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}
