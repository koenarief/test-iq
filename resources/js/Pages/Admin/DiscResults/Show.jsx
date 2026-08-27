import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const DIMENSIONS = [
    { key: 'd', label: 'D — Dominance' },
    { key: 'i', label: 'I — Influence' },
    { key: 's', label: 'S — Steadiness' },
    { key: 'c', label: 'C — Conscientiousness' },
];

function genderLabel(value) {
    if (value === 'L') return 'Laki-laki';
    if (value === 'P') return 'Perempuan';
    return '-';
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function asList(value) {
    if (Array.isArray(value)) return value;
    if (value && typeof value === 'object') return Object.values(value);
    return [];
}

export default function Show({ test, profile }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Hasil DISC — {test.participant_name}
                    </h2>
                    <Link href={route('admin.disc-results.index')}>
                        <SecondaryButton type="button">
                            Kembali ke Daftar
                        </SecondaryButton>
                    </Link>
                </div>
            }
        >
            <Head title={`Hasil DISC - ${test.participant_name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="grid gap-4 bg-white p-6 shadow-sm sm:rounded-lg md:grid-cols-3">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Peserta
                            </p>
                            <p className="mt-1 font-semibold text-gray-900">
                                {test.participant_name}
                            </p>
                            <p className="text-sm text-gray-600">
                                {test.age} tahun ·{' '}
                                {genderLabel(test.gender)}
                            </p>
                            <p className="mt-1 text-sm text-gray-600">
                                Merchant:{' '}
                                <span className="font-medium text-gray-800">
                                    {test.merchant_name ?? 'Umum'}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Waktu Pelaksanaan
                            </p>
                            <p className="mt-1 text-sm text-gray-700">
                                Mulai: {formatDate(test.started_at)}
                            </p>
                            <p className="text-sm text-gray-700">
                                Selesai: {formatDate(test.finished_at)}
                            </p>
                        </div>
                        <div className="rounded-lg bg-indigo-50 p-4">
                            <p className="text-xs uppercase tracking-wide text-indigo-600">
                                Tipe DISC
                            </p>
                            <p className="mt-1 text-3xl font-bold text-indigo-900">
                                {test.disc_type ?? '—'}
                            </p>
                            <p className="text-sm text-indigo-700">
                                Primer: {test.primary_type ?? '—'} · Sekunder:{' '}
                                {test.secondary_type ?? '—'}
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Dimensi
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Most
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Least
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Change
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Graph
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {DIMENSIONS.map(({ key, label }) => (
                                    <tr key={key}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {label}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-700">
                                            {test[`most_${key}`] ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-700">
                                            {test[`least_${key}`] ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-700">
                                            {test[`change_${key}`] ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-indigo-700">
                                            {test[`graph_${key}`] ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {profile && (
                        <div className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                            <div>
                                <p className="text-xs uppercase tracking-wide text-gray-500">
                                    Profil {profile.code}
                                </p>
                                <h3 className="mt-1 text-lg font-semibold text-gray-900">
                                    {profile.name} — {profile.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-gray-700">
                                    {profile.summary}
                                </p>
                            </div>

                            {asList(profile.job_match).length > 0 && (
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Kecocokan Pekerjaan
                                    </p>
                                    <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-gray-700">
                                        {asList(profile.job_match).map(
                                            (item, index) => (
                                                <li key={index}>{item}</li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
