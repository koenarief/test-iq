import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import AnswerKeyForm from './Partials/Form';

export default function Edit({ answerKey, subtests }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Ubah Kunci Jawaban {answerKey.subtest} #
                    {answerKey.question_number}
                </h2>
            }
        >
            <Head title="Ubah Kunci Jawaban" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <AnswerKeyForm
                            subtests={subtests}
                            answerKey={answerKey}
                            submitUrl={route(
                                'admin.ist-answer-keys.update',
                                answerKey.id,
                            )}
                            method="put"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
