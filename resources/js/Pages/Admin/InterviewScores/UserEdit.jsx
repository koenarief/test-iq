import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import AspectForm from './Partials/AspectForm';

const ASPECTS = [
    { key: 'interview_user_task_understanding', weightKey: 'task_understanding', label: 'Pemahaman Tugas dan Tanggung Jawab' },
    { key: 'interview_user_work_competence', weightKey: 'work_competence', label: 'Kemampuan dan Kompetensi Kerja' },
    { key: 'interview_user_problem_analysis', weightKey: 'problem_analysis', label: 'Analisis dan Penyelesaian Masalah' },
    { key: 'interview_user_work_quality', weightKey: 'work_quality', label: 'Ketelitian dan Kualitas Kerja' },
    { key: 'interview_user_initiative_independence', weightKey: 'initiative_independence', label: 'Inisiatif dan Kemandirian' },
    { key: 'interview_user_communication_teamwork', weightKey: 'communication_teamwork', label: 'Komunikasi dan Kerja Sama' },
];

export default function UserEdit({ candidateAssessment, weights }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Interview User
                </h2>
            }
        >
            <Head title="Interview User" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <AspectForm
                        title="Interview User"
                        aspects={ASPECTS}
                        notesKey="interview_user_notes"
                        candidateAssessment={candidateAssessment}
                        weights={weights}
                        updateRoute={route(
                            'admin.candidate-assessments.interview-user.update',
                            candidateAssessment.id,
                        )}
                        cancelRoute={route(
                            'admin.assessment-recaps.show',
                            candidateAssessment.id,
                        )}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
