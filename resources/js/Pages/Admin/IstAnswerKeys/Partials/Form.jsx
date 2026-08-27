import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';

export default function AnswerKeyForm({ subtests, answerKey, submitUrl, method = 'post' }) {
    const { data, setData, post, put, processing, errors } = useForm({
        subtest: answerKey?.subtest ?? subtests[0] ?? '',
        question_number: answerKey?.question_number ?? '',
        correct_answer: answerKey?.correct_answer ?? '',
        score_weight: answerKey?.score_weight ?? 1,
    });

    const submit = (e) => {
        e.preventDefault();

        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    const isGe = data.subtest === 'GE';

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="subtest" value="Subtes" />
                <select
                    id="subtest"
                    value={data.subtest}
                    onChange={(e) => setData('subtest', e.target.value)}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    {subtests.map((code) => (
                        <option key={code} value={code}>
                            {code}
                        </option>
                    ))}
                </select>
                <InputError message={errors.subtest} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="question_number" value="Nomor Soal" />
                <TextInput
                    id="question_number"
                    type="number"
                    min="1"
                    value={data.question_number}
                    onChange={(e) => setData('question_number', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.question_number} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="correct_answer" value="Jawaban Benar" />
                <textarea
                    id="correct_answer"
                    value={data.correct_answer}
                    onChange={(e) => setData('correct_answer', e.target.value)}
                    rows={isGe ? 5 : 2}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder={
                        isGe
                            ? '{"score_2":["kata kunci"],"score_1":["kata kunci lain"]}'
                            : 'contoh: a atau 27'
                    }
                />
                {isGe && (
                    <p className="mt-1 text-xs text-gray-500">
                        Subtes GE dinilai berdasarkan kata kunci berskala 0-2,
                        harus berupa JSON dengan kunci{' '}
                        <code>score_2</code> dan <code>score_1</code>.
                    </p>
                )}
                <InputError message={errors.correct_answer} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="score_weight" value="Bobot Skor" />
                <TextInput
                    id="score_weight"
                    type="number"
                    min="0"
                    value={data.score_weight}
                    onChange={(e) => setData('score_weight', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.score_weight} className="mt-2" />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                <Link href={route('admin.ist-answer-keys.index')}>
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}
