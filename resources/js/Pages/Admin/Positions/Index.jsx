import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ positions, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.positions.index'),
            { search },
            { preserveState: true, replace: true },
        );
    };

    const destroy = (position) => {
        if (!confirm(`Hapus posisi ${position.name}?`)) {
            return;
        }

        router.delete(route('admin.positions.destroy', position.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Posisi
                </h2>
            }
        >
            <Head title="Posisi" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-4 shadow-sm sm:rounded-lg">
                        <form
                            onSubmit={submitSearch}
                            className="flex items-center gap-2"
                        >
                            <TextInput
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama posisi..."
                            />
                            <SecondaryButton type="submit">
                                Cari
                            </SecondaryButton>
                        </form>

                        <Link href={route('admin.positions.create')}>
                            <PrimaryButton>Tambah Posisi</PrimaryButton>
                        </Link>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Nama
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Departemen
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Total Bobot
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {positions.data.map((position) => (
                                    <tr key={position.id}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {position.name}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {position.department}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs ${
                                                    Math.round(
                                                        position.total_weight *
                                                            100,
                                                    ) /
                                                        100 ===
                                                    100
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-red-100 text-red-700'
                                                }`}
                                            >
                                                {position.total_weight}%
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {position.is_active ? (
                                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                                    Aktif
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                    Nonaktif
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route(
                                                        'admin.positions.edit',
                                                        position.id,
                                                    )}
                                                >
                                                    <SecondaryButton type="button">
                                                        Ubah
                                                    </SecondaryButton>
                                                </Link>
                                                <DangerButton
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(position)
                                                    }
                                                >
                                                    Hapus
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {positions.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada posisi.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {positions.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {positions.links.map((link, index) => (
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
