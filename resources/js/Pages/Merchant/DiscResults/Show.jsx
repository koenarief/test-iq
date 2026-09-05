import SecondaryButton from '@/Components/SecondaryButton';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, Link } from '@inertiajs/react';
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const DIMENSIONS = [
    { key: 'd', label: 'D — Dominance' },
    { key: 'i', label: 'I — Influence' },
    { key: 's', label: 'S — Steadiness' },
    { key: 'c', label: 'C — Conscientiousness' },
];

// Slots 1-3 of the validated categorical palette (blue, orange, aqua) —
// the only three that clear the all-pairs CVD/contrast gates together.
const SERIES_COLORS = {
    change: '#2a78d6',
    most: '#eb6834',
    least: '#1baf7a',
};

function DiscTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;

    return (
        <div className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
            <p className="mb-1 font-semibold text-gray-500">
                Dimensi {label}
            </p>
            {payload.map((entry) => (
                <p
                    key={entry.dataKey}
                    className="font-semibold"
                    style={{ color: entry.stroke }}
                >
                    {entry.name}: {entry.value ?? '—'}
                </p>
            ))}
        </div>
    );
}

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
    const chartData = DIMENSIONS.map(({ key, label }) => ({
        subject: label.charAt(0),
        most: test[`most_graph_${key}`] ?? null,
        least: test[`least_graph_${key}`] ?? null,
        change: test[`graph_${key}`] ?? null,
    }));

    return (
        <MerchantLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Hasil DISC — {test.participant_name}
                    </h2>
                    <Link href={route('merchant.disc-results.index')}>
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
                                {test.age} tahun · {genderLabel(test.gender)}
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
                                Primer: {test.primary_type ?? '—'} ·
                                Sekunder: {test.secondary_type ?? '—'}
                            </p>
                        </div>
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-sm font-semibold text-gray-900">
                            Visualisasi Grafik DISC
                        </h3>
                        <p className="mt-1 text-xs text-gray-500">
                            Sebaran nilai Graph I (Most), Graph II (Least), dan
                            Graph III (Change/Hasil) pada tiap dimensi.
                        </p>

                        <div className="mt-4 h-80 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart
                                    data={chartData}
                                    margin={{
                                        top: 10,
                                        right: 20,
                                        left: 0,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="0"
                                        stroke="#e1e0d9"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="subject"
                                        stroke="#c3c2b7"
                                        tick={{
                                            fill: '#52514e',
                                            fontSize: 12,
                                            fontWeight: 600,
                                        }}
                                        tickLine={false}
                                        axisLine={{ stroke: '#c3c2b7' }}
                                    />
                                    <YAxis
                                        domain={[0, 100]}
                                        stroke="#c3c2b7"
                                        tick={{ fill: '#898781', fontSize: 12 }}
                                        tickLine={false}
                                        axisLine={false}
                                        width={32}
                                    />
                                    <Tooltip content={<DiscTooltip />} />
                                    <Legend
                                        wrapperStyle={{ fontSize: 12 }}
                                        formatter={(value) => (
                                            <span className="text-gray-600">
                                                {value}
                                            </span>
                                        )}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="most"
                                        name="Graph I (Most)"
                                        stroke={SERIES_COLORS.most}
                                        strokeWidth={2}
                                        dot={{
                                            r: 4,
                                            fill: SERIES_COLORS.most,
                                            stroke: '#ffffff',
                                            strokeWidth: 2,
                                        }}
                                        activeDot={{ r: 6 }}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="least"
                                        name="Graph II (Least)"
                                        stroke={SERIES_COLORS.least}
                                        strokeWidth={2}
                                        dot={{
                                            r: 4,
                                            fill: SERIES_COLORS.least,
                                            stroke: '#ffffff',
                                            strokeWidth: 2,
                                        }}
                                        activeDot={{ r: 6 }}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="change"
                                        name="Graph III (Change/Hasil)"
                                        stroke={SERIES_COLORS.change}
                                        strokeWidth={2}
                                        dot={{
                                            r: 4,
                                            fill: SERIES_COLORS.change,
                                            stroke: '#ffffff',
                                            strokeWidth: 2,
                                        }}
                                        activeDot={{ r: 6 }}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
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
                                        Graph I (Most)
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Graph II (Least)
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">
                                        Graph III (Change)
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
                                        <td className="px-4 py-3 text-right font-semibold text-gray-700">
                                            {test[`most_graph_${key}`] ??
                                                '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right font-semibold text-gray-700">
                                            {test[`least_graph_${key}`] ??
                                                '—'}
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
        </MerchantLayout>
    );
}
