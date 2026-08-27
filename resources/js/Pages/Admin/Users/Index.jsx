import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export default function Index({ users, filters }) {
    const { flash, errors, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.users.index'),
            { search },
            { preserveState: true, replace: true },
        );
    };

    const destroy = (user) => {
        if (!confirm(`Hapus user ${user.name}?`)) {
            return;
        }

        router.delete(route('admin.users.destroy', user.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Manajemen User
                </h2>
            }
        >
            <Head title="Manajemen User" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}
                    {errors?.user && (
                        <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">
                            {errors.user}
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
                                placeholder="Cari nama atau email..."
                            />
                            <SecondaryButton type="submit">
                                Cari
                            </SecondaryButton>
                        </form>

                        <Link href={route('admin.users.create')}>
                            <PrimaryButton>Tambah User</PrimaryButton>
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
                                        Email
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Terverifikasi
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Terdaftar
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {users.data.map((user) => (
                                    <tr key={user.id}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {user.name}
                                            {user.id === auth.user.id && (
                                                <span className="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">
                                                    Anda
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {user.email}
                                        </td>
                                        <td className="px-4 py-3">
                                            {user.email_verified_at ? (
                                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                                    Ya
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                    Belum
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {formatDate(user.created_at)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route(
                                                        'admin.users.edit',
                                                        user.id,
                                                    )}
                                                >
                                                    <SecondaryButton type="button">
                                                        Ubah
                                                    </SecondaryButton>
                                                </Link>
                                                <DangerButton
                                                    type="button"
                                                    disabled={
                                                        user.id ===
                                                        auth.user.id
                                                    }
                                                    onClick={() =>
                                                        destroy(user)
                                                    }
                                                >
                                                    Hapus
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {users.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Tidak ada user ditemukan.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {users.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {users.links.map((link, index) => (
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
