import SecondaryButton from '@/Components/SecondaryButton';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, Link } from '@inertiajs/react';

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

export default function Show({ test, breakdown = [] }) {
    return (
        <MerchantLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Hasil Kompetensi — {test.participant_name}
                    </h2>
                    <Link href={route('merchant.competency-results.index')}>
                        <SecondaryButton type="button">
                            Kembali ke Daftar
                        </SecondaryButton>
                    </Link>
                </div>
            }
        >
            <Head title={`Hasil Kompetensi - ${test.participant_name}`} />

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
                                Divisi & Skor
                            </p>
                            <p className="mt-1 text-lg font-bold text-indigo-900">
                                {test.department_label}
                            </p>
                            <p className="text-sm text-indigo-700">
                                Skor rata-rata:{' '}
                                {test.total_score !== null
                                    ? `${Number(test.total_score).toFixed(2)} / 5`
                                    : '—'}
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">
                                        Subtes / Kompetensi
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Soal Dijawab
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Rata-rata Poin
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Persentase
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {breakdown.map((row) => (
                                    <tr key={row.category_id}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {row.category_name}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-700">
                                            {row.answered_count}
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-gray-700">
                                            {row.avg_points.toFixed(2)} / 5
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-indigo-700">
                                            {row.percentage}%
                                        </td>
                                    </tr>
                                ))}
                                {breakdown.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-6 text-center text-gray-500"
                                        >
                                            Belum ada data jawaban.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}
