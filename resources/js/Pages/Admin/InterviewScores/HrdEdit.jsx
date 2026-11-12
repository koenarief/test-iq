import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head } from '@inertiajs/react';
import AspectForm from './Partials/AspectForm';

const ASPECTS = [
    { key: 'interview_hrd_attitude_ethics', weightKey: 'attitude_ethics', label: 'Sikap dan Etika' },
    { key: 'interview_hrd_communication', weightKey: 'communication', label: 'Komunikasi' },
    { key: 'interview_hrd_motivation_goals', weightKey: 'motivation_goals', label: 'Motivasi dan Tujuan Kerja' },
    { key: 'interview_hrd_discipline_responsibility', weightKey: 'discipline_responsibility', label: 'Kedisiplinan dan Tanggung Jawab' },
    { key: 'interview_hrd_commitment_readiness', weightKey: 'commitment_readiness', label: 'Komitmen dan Kesiapan Kerja' },
    { key: 'interview_hrd_adaptability_teamwork', weightKey: 'adaptability_teamwork', label: 'Adaptasi dan Kerja Sama' },
];

export default function HrdEdit({ candidateAssessment, weights, urls, layout }) {
    const Layout = layout === 'merchant' ? MerchantLayout : AuthenticatedLayout;

    return (
        <Layout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Interview HRD
                </h2>
            }
        >
            <Head title="Interview HRD" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <AspectForm
                        title="Interview HRD"
                        aspects={ASPECTS}
                        notesKey="interview_hrd_notes"
                        candidateAssessment={candidateAssessment}
                        weights={weights}
                        updateRoute={
                            urls?.update ??
                            route(
                                'admin.candidate-assessments.interview-hrd.update',
                                candidateAssessment.id,
                            )
                        }
                        cancelRoute={
                            urls?.cancel ??
                            route(
                                'admin.assessment-recaps.show',
                                candidateAssessment.id,
                            )
                        }
                    />
                </div>
            </div>
        </Layout>
    );
}
