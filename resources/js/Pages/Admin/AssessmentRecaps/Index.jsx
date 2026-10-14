import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

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

export default function Index({ assessments, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [decision, setDecision] = useState(filters.decision ?? '');

    const submitFilters = (e) => {
        e.preventDefault();
        router.get(
            route('admin.assessment-recaps.index'),
            { search, decision },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Rekap Kandidat
                </h2>
            }
        >
            <Head title="Rekap Kandidat" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <form
                        onSubmit={submitFilters}
                        className="flex flex-wrap items-center gap-2 bg-white p-4 shadow-sm sm:rounded-lg"
                    >
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama/email kandidat..."
                        />
                        <select
                            value={decision}
                            onChange={(e) => setDecision(e.target.value)}
                            className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua keputusan</option>
                            <option value="recommended">Recommended</option>
                            <option value="consider">Consider</option>
                            <option value="not_recommended">
                                Not Recommended
                            </option>
                        </select>
                        <SecondaryButton type="submit">Cari</SecondaryButton>
                    </form>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Kandidat
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Posisi
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Assessment Score
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Gate
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Job Fit
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Final Score
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Keputusan
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {assessments.data.map((assessment) => (
                                    <tr key={assessment.id}>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-gray-900">
                                                {assessment.candidate.name}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {assessment.candidate.email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {assessment.position?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {assessment.assessment_score ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {assessment.gate_passed === null ? (
                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                    Belum lengkap
                                                </span>
                                            ) : assessment.gate_passed ? (
                                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                                    Memenuhi Kriteria
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                                                    Di Bawah Kriteria
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {assessment.job_fit_score ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {assessment.final_score ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {assessment.decision ? (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs ${DECISION_CLASS[assessment.decision]}`}
                                                >
                                                    {DECISION_LABEL[assessment.decision]}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route(
                                                    'admin.assessment-recaps.show',
                                                    assessment.id,
                                                )}
                                            >
                                                <SecondaryButton type="button">
                                                    Lihat
                                                </SecondaryButton>
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {assessments.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada data kandidat.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {assessments.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {assessments.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    preserveScroll
                                    className={`rounded-md px-3 py-1 text-sm ${
                                        link.active
                                            ? 'bg-gray-800 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50'
                                    } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
