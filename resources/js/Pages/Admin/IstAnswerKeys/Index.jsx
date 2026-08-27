import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ answerKeys, subtests, filters }) {
    const { flash } = usePage().props;

    const filterBySubtest = (subtest) => {
        router.get(
            route('admin.ist-answer-keys.index'),
            subtest ? { subtest } : {},
            { preserveState: true, replace: true },
        );
    };

    const destroy = (answerKey) => {
        if (
            !confirm(
                `Hapus kunci jawaban ${answerKey.subtest} #${answerKey.question_number}?`,
            )
        ) {
            return;
        }

        router.delete(route('admin.ist-answer-keys.destroy', answerKey.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Kunci Jawaban IST
                </h2>
            }
        >
            <Head title="Kunci Jawaban IST" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-center gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => filterBySubtest(null)}
                                className={
                                    !filters.subtest
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                Semua
                            </SecondaryButton>
                            {subtests.map((code) => (
                                <SecondaryButton
                                    key={code}
                                    type="button"
                                    onClick={() => filterBySubtest(code)}
                                    className={
                                        filters.subtest === code
                                            ? 'ring-2 ring-indigo-500'
                                            : ''
                                    }
                                >
                                    {code}
                                </SecondaryButton>
                            ))}
                        </div>

                        <Link href={route('admin.ist-answer-keys.create')}>
                            <PrimaryButton>Tambah Kunci Jawaban</PrimaryButton>
                        </Link>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Subtes
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        No.
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Jawaban Benar
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Bobot
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {answerKeys.data.map((answerKey) => (
                                    <tr key={answerKey.id}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {answerKey.subtest}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {answerKey.question_number}
                                        </td>
                                        <td className="max-w-md truncate px-4 py-3 text-gray-700">
                                            {answerKey.correct_answer}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {answerKey.score_weight}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route(
                                                        'admin.ist-answer-keys.edit',
                                                        answerKey.id,
                                                    )}
                                                >
                                                    <SecondaryButton type="button">
                                                        Ubah
                                                    </SecondaryButton>
                                                </Link>
                                                <DangerButton
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(answerKey)
                                                    }
                                                >
                                                    Hapus
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {answerKeys.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada kunci jawaban.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {answerKeys.links.length > 3 && (
                        <div className="flex flex-wrap gap-2">
                            {answerKeys.links.map((link, index) => (
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
