import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_LABELS = {
    draft: 'Draft',
    in_progress: 'Berjalan',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

const STATUS_BADGE_CLASS = {
    draft: 'bg-gray-100 text-gray-600',
    in_progress: 'bg-amber-100 text-amber-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

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

export default function Index({ tests, statuses, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next) => {
        router.get(
            route('admin.ist-results.index'),
            { ...filters, ...next },
            { preserveState: true, replace: true },
        );
    };

    const submitSearch = (e) => {
        e.preventDefault();
        applyFilters({ search });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Hasil Tes IST
                </h2>
            }
        >
            <Head title="Hasil Tes IST" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-center gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => applyFilters({ status: '' })}
                                className={
                                    !filters.status
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                Semua
                            </SecondaryButton>
                            {statuses.map((status) => (
                                <SecondaryButton
                                    key={status}
                                    type="button"
                                    onClick={() =>
                                        applyFilters({ status })
                                    }
                                    className={
                                        filters.status === status
                                            ? 'ring-2 ring-indigo-500'
                                            : ''
                                    }
                                >
                                    {STATUS_LABELS[status] ?? status}
                                </SecondaryButton>
                            ))}
                        </div>

                        <form
                            onSubmit={submitSearch}
                            className="flex items-center gap-2"
                        >
                            <TextInput
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama peserta..."
                            />
                            <SecondaryButton type="submit">
                                Cari
                            </SecondaryButton>
                        </form>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Peserta
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Usia/JK
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Progres
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Mulai
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Selesai
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {tests.data.map((test) => (
                                    <tr key={test.id}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {test.participant_name}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {test.age} th /{' '}
                                            {genderLabel(test.gender)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs ${
                                                    STATUS_BADGE_CLASS[
                                                        test.status
                                                    ] ??
                                                    'bg-gray-100 text-gray-600'
                                                }`}
                                            >
                                                {STATUS_LABELS[
                                                    test.status
                                                ] ?? test.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {test.current_subtest_sequence}/9
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {formatDate(test.started_at)}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {formatDate(test.finished_at)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {test.status === 'completed' ? (
                                                <Link
                                                    href={route(
                                                        'admin.ist-results.show',
                                                        test.public_id,
                                                    )}
                                                >
                                                    <SecondaryButton type="button">
                                                        Lihat Hasil
                                                    </SecondaryButton>
                                                </Link>
                                            ) : (
                                                <span className="text-xs text-gray-400">
                                                    Belum selesai
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {tests.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada peserta untuk filter
                                            ini.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {tests.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {tests.links.map((link, index) => (
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
