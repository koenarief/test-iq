import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

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

function GateRow({ label, score, passed, min }) {
    return (
        <div className="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
            <span className="text-sm text-gray-600">{label}</span>
            <div className="flex items-center gap-2">
                <span className="font-medium text-gray-900">
                    {score ?? '—'}
                </span>
                <span className="text-xs text-gray-400">(min {min})</span>
                {passed === null ? null : passed ? (
                    <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                        PASS
                    </span>
                ) : (
                    <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">
                        FAIL
                    </span>
                )}
            </div>
        </div>
    );
}

export default function Show({ assessment }) {
    const breakdownByType = (type) =>
        (assessment.strength_development_breakdown ?? []).filter(
            (row) => row.type === type,
        );

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
                            Assessment Score
                        </h3>

                        <GateRow
                            label="DISC"
                            score={assessment.disc_score}
                            passed={assessment.gate_disc_passed}
                            min={60}
                        />
                        <GateRow
                            label="IST"
                            score={assessment.ist_score}
                            passed={assessment.gate_ist_passed}
                            min={60}
                        />
                        <GateRow
                            label="Competency"
                            score={assessment.competency_score}
                            passed={assessment.gate_competency_passed}
                            min={65}
                        />

                        <div className="mt-4 flex items-center justify-between rounded-md bg-gray-50 px-3 py-3">
                            <span className="font-semibold text-gray-700">
                                Assessment Score
                            </span>
                            <div className="flex items-center gap-2">
                                <span className="text-lg font-bold text-gray-900">
                                    {assessment.assessment_score ?? '—'}
                                </span>
                                {assessment.gate_passed === null ? (
                                    <span className="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
                                        Belum lengkap
                                    </span>
                                ) : assessment.gate_passed ? (
                                    <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        PASS
                                    </span>
                                ) : (
                                    <span className="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        FAIL / NOT RECOMMENDED
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {assessment.gate_passed && (
                        <>
                            {assessment.job_fit_score !== null && (
                                <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                                    <h3 className="mb-4 text-lg font-semibold text-gray-900">
                                        Job Fit Score —{' '}
                                        {assessment.job_fit_score}
                                    </h3>

                                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead>
                                            <tr>
                                                <th className="py-2 text-left font-medium text-gray-500">
                                                    Komponen
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
                                            {(
                                                assessment.job_fit_breakdown ??
                                                []
                                            ).map((row, index) => (
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
                                            {breakdownByType(type).map(
                                                (row) => (
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
                                                ),
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                                <div className="mb-4 flex items-center justify-between">
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        Interview Score
                                    </h3>
                                    <Link
                                        href={route(
                                            'admin.candidate-assessments.interview.edit',
                                            assessment.id,
                                        )}
                                    >
                                        <PrimaryButton type="button">
                                            {assessment.interview_score ===
                                            null
                                                ? 'Input Skor Interview'
                                                : 'Ubah Skor Interview'}
                                        </PrimaryButton>
                                    </Link>
                                </div>

                                {assessment.interview_score !== null ? (
                                    <div className="space-y-1 text-sm">
                                        <div className="flex justify-between border-b border-gray-100 py-1">
                                            <span>Communication (20%)</span>
                                            <span>
                                                {
                                                    assessment.interview_communication
                                                }
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b border-gray-100 py-1">
                                            <span>Motivation (15%)</span>
                                            <span>
                                                {assessment.interview_motivation}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b border-gray-100 py-1">
                                            <span>Problem Solving (20%)</span>
                                            <span>
                                                {
                                                    assessment.interview_problem_solving
                                                }
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b border-gray-100 py-1">
                                            <span>Job Knowledge (20%)</span>
                                            <span>
                                                {
                                                    assessment.interview_job_knowledge
                                                }
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b border-gray-100 py-1">
                                            <span>Attitude (15%)</span>
                                            <span>
                                                {assessment.interview_attitude}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b border-gray-100 py-1">
                                            <span>Work Alignment (10%)</span>
                                            <span>
                                                {
                                                    assessment.interview_work_alignment
                                                }
                                            </span>
                                        </div>
                                        <div className="mt-2 flex justify-between font-semibold">
                                            <span>Interview Score</span>
                                            <span>
                                                {assessment.interview_score}
                                            </span>
                                        </div>
                                        {assessment.interview_notes && (
                                            <p className="mt-3 rounded-md bg-gray-50 p-3 text-gray-600">
                                                {assessment.interview_notes}
                                            </p>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">
                                        Belum ada skor interview.
                                    </p>
                                )}
                            </div>

                            {assessment.final_score !== null && (
                                <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            Final Score
                                        </h3>
                                        <div className="flex items-center gap-3">
                                            <span className="text-2xl font-bold text-gray-900">
                                                {assessment.final_score}
                                            </span>
                                            <span
                                                className={`rounded-full px-3 py-1 text-sm font-semibold ${DECISION_CLASS[assessment.decision]}`}
                                            >
                                                {
                                                    DECISION_LABEL[
                                                        assessment.decision
                                                    ]
                                                }
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {assessment.gate_passed === false && (
                        <div className="rounded-lg bg-red-50 p-6 text-center text-red-800 shadow-sm">
                            <p className="text-lg font-semibold">
                                NOT RECOMMENDED
                            </p>
                            <p className="mt-1 text-sm">
                                Kandidat tidak memenuhi Minimum Score Gate
                                pada Assessment Score.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
