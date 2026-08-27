import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import QuestionForm from './Partials/Form';

export default function Edit({
    question,
    subtests,
    answerTypes,
    difficulties,
    kinds,
    optionKeys,
}) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Ubah Soal #{question.question_number}
                </h2>
            }
        >
            <Head title="Ubah Soal IST" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <QuestionForm
                            subtests={subtests}
                            answerTypes={answerTypes}
                            difficulties={difficulties}
                            kinds={kinds}
                            optionKeys={optionKeys}
                            question={question}
                            submitUrl={route(
                                'admin.ist-questions.update',
                                question.id,
                            )}
                            method="put"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
