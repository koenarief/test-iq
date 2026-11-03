import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, Link, router } from '@inertiajs/react';

function StartUrlRow({ label, url }) {
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </p>
            <div className="mt-1 flex items-stretch gap-2">
                <input
                    readOnly
                    value={url}
                    onFocus={(e) => e.target.select()}
                    className="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-700 shadow-sm"
                />
                <a
                    href={url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Buka
                </a>
            </div>
        </div>
    );
}

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

const HEADERS = [
    'Peringkat',
    'Kandidat',
    'Posisi',
    'DISC',
    'IST',
    'Kompetensi',
    'Assessment',
    'Gate',
    'Job Fit',
    'Final Score',
    'Keputusan',
];

export default function Dashboard({
    stats,
    ranking,
    startUrl,
    filters = {},
    positions = [],
}) {
    const applyFilter = (key, value) => {
        router.get(
            route('merchant.dashboard'),
            Object.fromEntries(
                Object.entries({ ...filters, [key]: value }).filter(
                    ([, v]) => v !== '' && v != null,
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };
    const hasFilter = filters.from || filters.to || filters.position_id;

    return (
        <MerchantLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard Merchant
                </h2>
            }
        >
            <Head title="Dashboard Merchant" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Total Peserta IST
                            </p>
                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {stats.ist_total}
                            </p>
                            <p className="mt-1 text-xs text-green-700">
                                {stats.ist_completed} selesai
                            </p>
                        </div>
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Total Peserta DISC
                            </p>
                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {stats.disc_total}
                            </p>
                            <p className="mt-1 text-xs text-green-700">
                                {stats.disc_completed} selesai
                            </p>
                        </div>
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Total Peserta Kompetensi
                            </p>
                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {stats.competency_total}
                            </p>
                            <p className="mt-1 text-xs text-green-700">
                                {stats.competency_completed} selesai
                            </p>
                        </div>
                    </div>

                    <div className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900">
                                Link Tes untuk Peserta
                            </h3>
                            <p className="mt-1 text-xs text-gray-500">
                                Bagikan link ini ke peserta Anda. Hasil tes
                                mereka otomatis masuk ke dashboard ini.
                            </p>
                        </div>

                        <StartUrlRow label="Link Tes" url={startUrl} />
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 p-6">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Rekap &amp; Peringkat Kandidat
                            </h3>
                            <p className="mt-1 text-xs text-gray-500">
                                Diurutkan berdasarkan Final Score, lalu Job Fit
                                dan Assessment Score.
                            </p>
                            <div className="mt-4 flex flex-wrap items-end gap-3">
                                <label className="text-xs text-gray-600">
                                    Dari tanggal
                                    <input
                                        type="date"
                                        value={filters.from ?? ''}
                                        onChange={(e) => applyFilter('from', e.target.value)}
                                        className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm"
                                    />
                                </label>
                                <label className="text-xs text-gray-600">
                                    Sampai tanggal
                                    <input
                                        type="date"
                                        value={filters.to ?? ''}
                                        onChange={(e) => applyFilter('to', e.target.value)}
                                        className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm"
                                    />
                                </label>
                                <label className="text-xs text-gray-600">
                                    Posisi
                                    <select
                                        value={filters.position_id ?? ''}
                                        onChange={(e) => applyFilter('position_id', e.target.value)}
                                        className="mt-1 block rounded-md border-gray-300 text-sm shadow-sm"
                                    >
                                        <option value="">Semua posisi</option>
                                        {positions.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                {hasFilter && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.get(route('merchant.dashboard'), {}, { replace: true })
                                        }
                                        className="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Reset
                                    </button>
                                )}
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        {HEADERS.map((h) => (
                                            <th
                                                key={h}
                                                className="px-4 py-3 text-left font-medium text-gray-500"
                                            >
                                                {h}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {ranking.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-3 font-semibold text-gray-900">
                                                #{r.rank}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-gray-900">
                                                {r.name}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {r.position ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {r.disc_score ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {r.ist_score ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {r.competency_score ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-700">
                                                {r.assessment_score ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.gate_passed === null ? (
                                                    <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                        Belum lengkap
                                                    </span>
                                                ) : r.gate_passed ? (
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
                                                {r.job_fit_score ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 font-semibold text-gray-900">
                                                {r.final_score ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.decision ? (
                                                    <span
                                                        className={`rounded-full px-2 py-0.5 text-xs ${DECISION_CLASS[r.decision]}`}
                                                    >
                                                        {DECISION_LABEL[r.decision]}
                                                    </span>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {ranking.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={HEADERS.length}
                                                className="px-4 py-6 text-center text-gray-500"
                                            >
                                                Belum ada kandidat.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-4">
                        <Link
                            href={route('merchant.ist-results.index')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                        >
                            Lihat Hasil IST
                        </Link>
                        <Link
                            href={route('merchant.disc-results.index')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                        >
                            Lihat Hasil DISC
                        </Link>
                        <Link
                            href={route('merchant.competency-results.index')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                        >
                            Lihat Hasil Kompetensi
                        </Link>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}
