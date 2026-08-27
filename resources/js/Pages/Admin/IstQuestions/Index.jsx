import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ subtests, questions, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next) => {
        router.get(
            route('admin.ist-questions.index'),
            { ...filters, ...next },
            { preserveState: true, replace: true },
        );
    };

    const submitSearch = (e) => {
        e.preventDefault();
        applyFilters({ search });
    };

    const destroy = (question) => {
        if (!confirm(`Hapus soal #${question.question_number}?`)) {
            return;
        }

        router.delete(route('admin.ist-questions.destroy', question.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Bank Soal IST
                </h2>
            }
        >
            <Head title="Bank Soal IST" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="flex flex-wrap items-center gap-2 bg-white p-4 shadow-sm sm:rounded-lg">
                        {subtests.map((subtest) => (
                            <SecondaryButton
                                key={subtest.id}
                                type="button"
                                onClick={() =>
                                    applyFilters({ subtest_id: subtest.id })
                                }
                                className={
                                    Number(filters.subtest_id) === subtest.id
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                {subtest.code}
                            </SecondaryButton>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-center gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => applyFilters({ kind: '' })}
                                className={
                                    !filters.kind
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                Semua Jenis
                            </SecondaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={() =>
                                    applyFilters({ kind: 'scored' })
                                }
                                className={
                                    filters.kind === 'scored'
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                Dinilai
                            </SecondaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={() =>
                                    applyFilters({ kind: 'example' })
                                }
                                className={
                                    filters.kind === 'example'
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                Contoh
                            </SecondaryButton>

                            <form
                                onSubmit={submitSearch}
                                className="flex items-center gap-2"
                            >
                                <TextInput
                                    value={search}
                                    onChange={(e) =>
                                        setSearch(e.target.value)
                                    }
                                    placeholder="Cari teks soal..."
                                />
                                <SecondaryButton type="submit">
                                    Cari
                                </SecondaryButton>
                            </form>
                        </div>

                        <Link href={route('admin.ist-questions.create')}>
                            <PrimaryButton>Tambah Soal</PrimaryButton>
                        </Link>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        No.
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Subtes
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Jenis
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Tipe Jawaban
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Teks Soal
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Opsi
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Aktif
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {questions.data.map((question) => (
                                    <tr key={question.id}>
                                        <td className="px-4 py-3 text-gray-700">
                                            {question.question_number}
                                        </td>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {question.subtest?.code}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {question.kind}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {question.answer_type}
                                        </td>
                                        <td className="max-w-sm truncate px-4 py-3 text-gray-700">
                                            {question.prompt}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {question.options_count}
                                        </td>
                                        <td className="px-4 py-3">
                                            {question.is_active ? (
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
                                                        'admin.ist-questions.edit',
                                                        question.id,
                                                    )}
                                                >
                                                    <SecondaryButton type="button">
                                                        Ubah
                                                    </SecondaryButton>
                                                </Link>
                                                <DangerButton
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(question)
                                                    }
                                                >
                                                    Hapus
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {questions.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada soal untuk filter ini.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {questions.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {questions.links.map((link, index) => (
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
