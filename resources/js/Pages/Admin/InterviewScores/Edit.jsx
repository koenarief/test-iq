import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const ASPECTS = [
    { key: 'interview_communication', weightKey: 'communication', label: 'Communication' },
    { key: 'interview_motivation', weightKey: 'motivation', label: 'Motivation' },
    { key: 'interview_problem_solving', weightKey: 'problem_solving', label: 'Problem Solving' },
    { key: 'interview_job_knowledge', weightKey: 'job_knowledge', label: 'Job Knowledge' },
    { key: 'interview_attitude', weightKey: 'attitude', label: 'Attitude' },
    { key: 'interview_work_alignment', weightKey: 'work_alignment', label: 'Work Alignment' },
];

export default function Edit({ candidateAssessment, weights }) {
    const { data, setData, put, processing, errors } = useForm({
        interview_communication: candidateAssessment.interview_communication ?? '',
        interview_motivation: candidateAssessment.interview_motivation ?? '',
        interview_problem_solving: candidateAssessment.interview_problem_solving ?? '',
        interview_job_knowledge: candidateAssessment.interview_job_knowledge ?? '',
        interview_attitude: candidateAssessment.interview_attitude ?? '',
        interview_work_alignment: candidateAssessment.interview_work_alignment ?? '',
        interview_notes: candidateAssessment.interview_notes ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(
            route(
                'admin.candidate-assessments.interview.update',
                candidateAssessment.id,
            ),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Skor Interview — {candidateAssessment.candidate.name}
                </h2>
            }
        >
            <Head title="Skor Interview" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            {ASPECTS.map((aspect) => (
                                <div key={aspect.key}>
                                    <InputLabel
                                        htmlFor={aspect.key}
                                        value={`${aspect.label} (bobot ${Math.round(weights[aspect.weightKey] * 100)}%)`}
                                    />
                                    <TextInput
                                        id={aspect.key}
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value={data[aspect.key]}
                                        onChange={(e) =>
                                            setData(
                                                aspect.key,
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 block w-full"
                                        required
                                    />
                                    <InputError
                                        message={errors[aspect.key]}
                                        className="mt-2"
                                    />
                                </div>
                            ))}

                            <div>
                                <InputLabel
                                    htmlFor="interview_notes"
                                    value="Catatan (opsional)"
                                />
                                <textarea
                                    id="interview_notes"
                                    value={data.interview_notes}
                                    onChange={(e) =>
                                        setData(
                                            'interview_notes',
                                            e.target.value,
                                        )
                                    }
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError
                                    message={errors.interview_notes}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    Simpan
                                </PrimaryButton>
                                <Link
                                    href={route(
                                        'admin.assessment-recaps.show',
                                        candidateAssessment.id,
                                    )}
                                >
                                    <SecondaryButton type="button">
                                        Batal
                                    </SecondaryButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
