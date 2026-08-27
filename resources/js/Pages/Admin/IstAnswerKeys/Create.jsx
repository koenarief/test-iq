import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import AnswerKeyForm from './Partials/Form';

export default function Create({ subtests }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tambah Kunci Jawaban
                </h2>
            }
        >
            <Head title="Tambah Kunci Jawaban" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <AnswerKeyForm
                            subtests={subtests}
                            submitUrl={route('admin.ist-answer-keys.store')}
                            method="post"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
