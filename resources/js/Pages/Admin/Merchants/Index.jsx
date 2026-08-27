import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ merchants, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.merchants.index'),
            { search },
            { preserveState: true, replace: true },
        );
    };

    const destroy = (merchant) => {
        if (
            !confirm(
                `Hapus merchant ${merchant.name}? Data tes yang sudah ada tidak akan terhapus, hanya tidak lagi terkait merchant ini.`,
            )
        ) {
            return;
        }

        router.delete(route('admin.merchants.destroy', merchant.public_id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Merchant
                </h2>
            }
        >
            <Head title="Merchant" />

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
                                placeholder="Cari nama merchant..."
                            />
                            <SecondaryButton type="submit">
                                Cari
                            </SecondaryButton>
                        </form>

                        <Link href={route('admin.merchants.create')}>
                            <PrimaryButton>Tambah Merchant</PrimaryButton>
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
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Peserta IST
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Peserta DISC
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Link Tes Peserta
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {merchants.data.map((merchant) => (
                                    <tr key={merchant.id}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {merchant.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            {merchant.is_active ? (
                                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                                    Aktif
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                    Nonaktif
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {merchant.ist_tests_count}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {merchant.disc_tests_count}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-2">
                                                <a
                                                    href={route(
                                                        'ist.index.merchant',
                                                        merchant.public_id,
                                                    )}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100"
                                                >
                                                    Link IST
                                                </a>
                                                <a
                                                    href={route(
                                                        'disc.index.merchant',
                                                        merchant.public_id,
                                                    )}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                                >
                                                    Link DISC
                                                </a>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route(
                                                        'admin.merchants.edit',
                                                        merchant.public_id,
                                                    )}
                                                >
                                                    <SecondaryButton type="button">
                                                        Ubah
                                                    </SecondaryButton>
                                                </Link>
                                                <DangerButton
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(merchant)
                                                    }
                                                >
                                                    Hapus
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {merchants.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada merchant.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {merchants.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {merchants.links.map((link, index) => (
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
