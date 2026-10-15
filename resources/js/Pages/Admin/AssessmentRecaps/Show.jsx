import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const DECISION_LABEL = {
    recommended: 'Recommended',
    consider: 'Consider',
    not_recommended: 'Not Recommended',
};

const DECISION_CLASS = {
    recommended: 'bg-green-100 text-green-700',
    consider: 'bg-amber-100 text-amber-700',
    not_recommended: 'bg-red-100 text-red-700',
};

const BAND_CLASS = {
    Exceptional: 'bg-emerald-100 text-emerald-700',
    Strong: 'bg-green-100 text-green-700',
    Adequate: 'bg-amber-100 text-amber-700',
    'Development Area': 'bg-orange-100 text-orange-700',
    Weakness: 'bg-red-100 text-red-700',
};

const HRD_ASPECTS = [
    { key: 'interview_hrd_attitude_ethics', label: 'Sikap dan Etika', weight: 20 },
    { key: 'interview_hrd_communication', label: 'Komunikasi', weight: 15 },
    { key: 'interview_hrd_motivation_goals', label: 'Motivasi dan Tujuan Kerja', weight: 15 },
    { key: 'interview_hrd_discipline_responsibility', label: 'Kedisiplinan dan Tanggung Jawab', weight: 20 },
    { key: 'interview_hrd_commitment_readiness', label: 'Komitmen dan Kesiapan Kerja', weight: 15 },
    { key: 'interview_hrd_adaptability_teamwork', label: 'Adaptasi dan Kerja Sama', weight: 15 },
];

const USER_ASPECTS = [
    { key: 'interview_user_task_understanding', label: 'Pemahaman Tugas dan Tanggung Jawab', weight: 20 },
    { key: 'interview_user_work_competence', label: 'Kemampuan dan Kompetensi Kerja', weight: 25 },
    { key: 'interview_user_problem_analysis', label: 'Analisis dan Penyelesaian Masalah', weight: 15 },
    { key: 'interview_user_work_quality', label: 'Ketelitian dan Kualitas Kerja', weight: 15 },
    { key: 'interview_user_initiative_independence', label: 'Inisiatif dan Kemandirian', weight: 10 },
    { key: 'interview_user_communication_teamwork', label: 'Komunikasi dan Kerja Sama', weight: 15 },
];

/**
 * Indicator-only row — konsep-hrd.pdf §3/§10.A: DISC/IST minimums and the
 * Assessment Score's own 75-point threshold are informational labels
 * ("Memenuhi Kriteria" / "Di Bawah Kriteria"), never a pass/fail gate that
 * blocks anything downstream.
 */
function IndicatorRow({ label, score, meetsCriteria, min }) {
    return (
        <div className="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
            <span className="text-sm text-gray-600">{label}</span>
            <div className="flex items-center gap-2">
                <span className="font-medium text-gray-900">
                    {score ?? '—'}
                </span>
                {min !== undefined && (
                    <span className="text-xs text-gray-400">(min {min})</span>
                )}
                {meetsCriteria === null || meetsCriteria === undefined ? null : meetsCriteria ? (
                    <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                        Memenuhi Kriteria
                    </span>
                ) : (
                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                        Di Bawah Kriteria
                    </span>
                )}
            </div>
        </div>
    );
}

function recomputeJobFit(assessmentId) {
    if (!confirm('Hitung ulang Job Fit Score memakai bobot posisi terbaru?')) {
        return;
    }

    router.post(
        route('admin.candidate-assessments.recompute-job-fit', assessmentId),
        {},
        { preserveScroll: true },
    );
}

const DISC_LABELS = {
    D: 'Dominance',
    I: 'Influence',
    S: 'Steadiness',
    C: 'Conscientiousness',
};

export default function Show({ assessment }) {
    const breakdownByType = (type) =>
        (assessment.strength_development_breakdown ?? []).filter(
            (row) => row.type === type,
        );

    const jobFitRows = assessment.job_fit_breakdown?.rows ?? [];
    const discRows = assessment.disc_breakdown?.rows ?? [];
    const hrdDone = assessment.interview_hrd_scored_at !== null;
    const userDone = assessment.interview_user_scored_at !== null;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Rekap — {assessment.candidate.name}
                </h2>
            }
        >
            <Head title={`Rekap — ${assessment.candidate.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div>
                            <div className="font-semibold text-gray-900">
                                {assessment.candidate.name}
                            </div>
                            <div className="text-sm text-gray-500">
                                {assessment.candidate.email}
                            </div>
                        </div>
                        <div className="text-sm text-gray-600">
                            Posisi:{' '}
                            <span className="font-semibold text-gray-900">
                                {assessment.position?.name ?? '—'}
                            </span>
                        </div>
                        <Link href={route('admin.assessment-recaps.index')}>
                            <SecondaryButton type="button">
                                Kembali
                            </SecondaryButton>
                        </Link>
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900">
                            Assessment Score (DISC 50% + IST 50%)
                        </h3>

                        <IndicatorRow
                            label="DISC"
                            score={assessment.disc_score}
                            meetsCriteria={assessment.gate_disc_passed}
                            min={60}
                        />
                        {discRows.length > 0 && (
                            <div className="mb-2 ml-4 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-600">
                                <div className="mb-1 font-semibold">
                                    DISC Fit Score — bobot posisi
                                </div>
                                {discRows.map((row) => (
                                    <div
                                        key={row.dimension}
                                        className="flex justify-between"
                                    >
                                        <span>
                                            {DISC_LABELS[row.dimension]} (
                                            {row.weight}%)
                                        </span>
                                        <span>
                                            {row.value} × {row.weight}% ={' '}
                                            {row.contribution}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                        <IndicatorRow
                            label="IST"
                            score={assessment.ist_score}
                            meetsCriteria={assessment.gate_ist_passed}
                            min={60}
                        />
                        <IndicatorRow
                            label="Competency (nilai mentah, informasi — tidak masuk Assessment)"
                            score={assessment.competency_score}
                        />

                        <div className="mt-4 flex items-center justify-between rounded-md bg-gray-50 px-3 py-3">
                            <span className="font-semibold text-gray-700">
                                Assessment Score
                            </span>
                            <div className="flex items-center gap-2">
                                <span className="text-lg font-bold text-gray-900">
                                    {assessment.assessment_score ?? '—'}
                                </span>
                                {assessment.gate_assessment_passed === null ? (
                                    <span className="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
                                        Belum lengkap
                                    </span>
                                ) : assessment.gate_assessment_passed ? (
                                    <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Memenuhi Kriteria
                                    </span>
                                ) : (
                                    <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Di Bawah Kriteria
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {assessment.job_fit_score !== null && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Job Fit Score — {assessment.job_fit_score}
                                    {assessment.job_fit_breakdown
                                        ?.position_name && (
                                        <span className="ml-2 text-sm font-normal text-gray-500">
                                            (bobot posisi:{' '}
                                            {
                                                assessment.job_fit_breakdown
                                                    .position_name
                                            }
                                            )
                                        </span>
                                    )}
                                </h3>
                                <SecondaryButton
                                    type="button"
                                    onClick={() =>
                                        recomputeJobFit(assessment.id)
                                    }
                                >
                                    Hitung Ulang Job Fit
                                </SecondaryButton>
                            </div>

                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr>
                                        <th className="py-2 text-left font-medium text-gray-500">
                                            Subtes
                                        </th>
                                        <th className="py-2 text-right font-medium text-gray-500">
                                            Bobot
                                        </th>
                                        <th className="py-2 text-right font-medium text-gray-500">
                                            Nilai
                                        </th>
                                        <th className="py-2 text-right font-medium text-gray-500">
                                            Kontribusi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {jobFitRows.map((row, index) => (
                                        <tr key={index}>
                                            <td className="py-2">
                                                {row.label}
                                            </td>
                                            <td className="py-2 text-right">
                                                {row.weight}%
                                            </td>
                                            <td className="py-2 text-right">
                                                {row.value}
                                            </td>
                                            <td className="py-2 text-right">
                                                {row.contribution}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {assessment.strength_development_breakdown && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-lg font-semibold text-gray-900">
                                Strength & Development Area
                            </h3>

                            {['competency', 'disc', 'ist'].map((type) => (
                                <div key={type} className="mb-4">
                                    <h4 className="mb-2 text-sm font-semibold uppercase text-gray-500">
                                        {type}
                                    </h4>
                                    <div className="space-y-1">
                                        {breakdownByType(type).map((row) => (
                                            <div
                                                key={row.key}
                                                className="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0"
                                            >
                                                <span className="text-sm text-gray-700">
                                                    {row.label}
                                                </span>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {row.score}
                                                    </span>
                                                    <span
                                                        className={`rounded-full px-2 py-0.5 text-xs ${BAND_CLASS[row.band]}`}
                                                    >
                                                        {row.band}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900">
                            Interview (HRD 40% + User 60%)
                        </h3>

                        <div className="mb-4">
                            <div className="mb-2 flex items-center justify-between">
                                <h4 className="text-sm font-semibold uppercase text-gray-500">
                                    Interview HRD
                                </h4>
                                <Link
                                    href={route(
                                        'admin.candidate-assessments.interview-hrd.edit',
                                        assessment.id,
                                    )}
                                >
                                    <PrimaryButton type="button">
                                        {hrdDone
                                            ? 'Ubah Interview HRD'
                                            : 'Lanjut Interview HRD'}
                                    </PrimaryButton>
                                </Link>
                            </div>

                            {hrdDone ? (
                                <div className="space-y-1 text-sm">
                                    {HRD_ASPECTS.map((aspect) => (
                                        <div
                                            key={aspect.key}
                                            className="flex justify-between border-b border-gray-100 py-1"
                                        >
                                            <span>
                                                {aspect.label} (
                                                {aspect.weight}%)
                                            </span>
                                            <span>
                                                {assessment[aspect.key]}
                                            </span>
                                        </div>
                                    ))}
                                    <div className="mt-2 flex justify-between font-semibold">
                                        <span>Skor HRD</span>
                                        <span>
                                            {assessment.interview_hrd_score}
                                        </span>
                                    </div>
                                    {assessment.interview_hrd_notes && (
                                        <p className="mt-3 rounded-md bg-gray-50 p-3 text-gray-600">
                                            {assessment.interview_hrd_notes}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500">
                                    Belum ada skor Interview HRD.
                                </p>
                            )}
                        </div>

                        {hrdDone && (
                            <div className="mb-4">
                                <div className="mb-2 flex items-center justify-between">
                                    <h4 className="text-sm font-semibold uppercase text-gray-500">
                                        Interview User
                                    </h4>
                                    <Link
                                        href={route(
                                            'admin.candidate-assessments.interview-user.edit',
                                            assessment.id,
                                        )}
                                    >
                                        <PrimaryButton type="button">
                                            {userDone
                                                ? 'Ubah Interview User'
                                                : 'Lanjut Interview User'}
                                        </PrimaryButton>
                                    </Link>
                                </div>

                                {userDone ? (
                                    <div className="space-y-1 text-sm">
                                        {USER_ASPECTS.map((aspect) => (
                                            <div
                                                key={aspect.key}
                                                className="flex justify-between border-b border-gray-100 py-1"
                                            >
                                                <span>
                                                    {aspect.label} (
                                                    {aspect.weight}%)
                                                </span>
                                                <span>
                                                    {assessment[aspect.key]}
                                                </span>
                                            </div>
                                        ))}
                                        <div className="mt-2 flex justify-between font-semibold">
                                            <span>Skor User</span>
                                            <span>
                                                {
                                                    assessment.interview_user_score
                                                }
                                            </span>
                                        </div>
                                        {assessment.interview_user_notes && (
                                            <p className="mt-3 rounded-md bg-gray-50 p-3 text-gray-600">
                                                {
                                                    assessment.interview_user_notes
                                                }
                                            </p>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">
                                        Belum ada skor Interview User.
                                    </p>
                                )}
                            </div>
                        )}

                        {assessment.interview_score !== null && (
                            <div className="mt-4 flex items-center justify-between rounded-md bg-gray-50 px-3 py-3">
                                <span className="font-semibold text-gray-700">
                                    Interview Score (gabungan)
                                </span>
                                <span className="text-lg font-bold text-gray-900">
                                    {assessment.interview_score}
                                </span>
                            </div>
                        )}
                    </div>

                    {assessment.final_score !== null && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Final Score (Assessment 35% + Job Fit 35%
                                    + Interview 30%)
                                </h3>
                                <div className="flex items-center gap-3">
                                    <span className="text-2xl font-bold text-gray-900">
                                        {assessment.final_score}
                                    </span>
                                    <span
                                        className={`rounded-full px-3 py-1 text-sm font-semibold ${DECISION_CLASS[assessment.decision]}`}
                                    >
                                        {DECISION_LABEL[assessment.decision]}
                                    </span>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
