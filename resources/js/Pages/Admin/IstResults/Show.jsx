import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function formatDuration(seconds) {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const secs = total % 60;

    return [
        hours > 0 ? `${hours} jam` : null,
        minutes > 0 ? `${minutes} menit` : null,
        `${secs} detik`,
    ]
        .filter(Boolean)
        .join(' ');
}

function formatNumber(value) {
    if (value === null || value === '' || typeof value === 'boolean') {
        return '—';
    }
    const numeric = Number(value);
    return Number.isFinite(numeric)
        ? new Intl.NumberFormat('id-ID').format(numeric)
        : '—';
}

function genderLabel(value) {
    if (value === 'L') return 'Laki-laki';
    if (value === 'P') return 'Perempuan';
    return '-';
}

export default function Show({ test, result, unavailableReason }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Hasil IST — {test.participant_name}
                    </h2>
                    <Link href={route('admin.ist-results.index')}>
                        <SecondaryButton type="button">
                            Kembali ke Daftar
                        </SecondaryButton>
                    </Link>
                </div>
            }
        >
            <Head title={`Hasil IST - ${test.participant_name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {!result && (
                        <div className="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                            Hasil belum bisa ditampilkan
                            {unavailableReason
                                ? `: ${unavailableReason}`
                                : '.'}
                        </div>
                    )}

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
                            {result && (
                                <p className="text-sm text-gray-700">
                                    Durasi:{' '}
                                    {formatDuration(result.durationSeconds)}
                                </p>
                            )}
                        </div>
                        {result && (
                            <div className="rounded-lg bg-indigo-50 p-4">
                                <p className="text-xs uppercase tracking-wide text-indigo-600">
                                    Skor IQ
                                </p>
                                <p className="mt-1 text-3xl font-bold text-indigo-900">
                                    {formatNumber(result.iqScore)}
                                </p>
                                <p className="text-sm text-indigo-700">
                                    {result.iqCategory ?? '—'}
                                </p>
                            </div>
                        )}
                    </div>

                    {result && (
                        <>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="bg-white p-4 shadow-sm sm:rounded-lg">
                                    <p className="text-xs uppercase tracking-wide text-gray-500">
                                        Total RW
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                                        {formatNumber(result.totalRawScore)}
                                    </p>
                                </div>
                                <div className="bg-white p-4 shadow-sm sm:rounded-lg">
                                    <p className="text-xs uppercase tracking-wide text-gray-500">
                                        Total SW
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                                        {formatNumber(
                                            result.totalStandardScore,
                                        )}
                                    </p>
                                </div>
                                <div className="bg-white p-4 shadow-sm sm:rounded-lg">
                                    <p className="text-xs uppercase tracking-wide text-gray-500">
                                        Profil Dominasi
                                    </p>
                                    <p className="mt-1 text-lg font-semibold text-gray-900">
                                        {result.dominanceProfile ?? '—'}
                                    </p>
                                </div>
                            </div>

                            <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium text-gray-500">
                                                Subtes
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium text-gray-500">
                                                RW
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium text-gray-500">
                                                SW
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium text-gray-500">
                                                Benar
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium text-gray-500">
                                                Parsial
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium text-gray-500">
                                                Salah
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium text-gray-500">
                                                Kosong
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {result.subtests.map((subtest) => (
                                            <tr key={subtest.code}>
                                                <td className="px-4 py-3 font-medium text-gray-900">
                                                    <span className="mr-2 font-mono text-indigo-600">
                                                        {subtest.code}
                                                    </span>
                                                    {subtest.name}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-700">
                                                    {formatNumber(
                                                        subtest.rawScore,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right font-semibold text-indigo-700">
                                                    {formatNumber(
                                                        subtest.standardScore,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-700">
                                                    {formatNumber(
                                                        subtest.correctCount,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-700">
                                                    {formatNumber(
                                                        subtest.partialCount,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-700">
                                                    {formatNumber(
                                                        subtest.wrongCount,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-700">
                                                    {formatNumber(
                                                        subtest.blankCount,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
