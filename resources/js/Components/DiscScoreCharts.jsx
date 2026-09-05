import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const DIMENSIONS = [
    { key: 'd', label: 'D' },
    { key: 'i', label: 'I' },
    { key: 's', label: 'S' },
    { key: 'c', label: 'C' },
];

// Slots 1-3 of the validated categorical palette (blue, orange, aqua) —
// the only three that clear the all-pairs CVD/contrast gates together.
// Each mini chart is single-series, so the color also carries no shared
// legend — the panel title alone identifies what's plotted.
const PANELS = [
    {
        key: 'most',
        title: 'Graph I — Most',
        field: (key) => `most_graph_${key}`,
        color: '#eb6834',
    },
    {
        key: 'least',
        title: 'Graph II — Least',
        field: (key) => `least_graph_${key}`,
        color: '#1baf7a',
    },
    {
        key: 'change',
        title: 'Graph III — Change/Hasil',
        field: (key) => `graph_${key}`,
        color: '#2a78d6',
    },
];

function PanelTooltip({ active, payload, label, color }) {
    if (!active || !payload?.length) return null;

    return (
        <div className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
            <p className="mb-1 font-semibold text-gray-500">Dimensi {label}</p>
            <p className="font-semibold" style={{ color }}>
                {payload[0].value ?? '—'}
            </p>
        </div>
    );
}

export default function DiscScoreCharts({ test }) {
    return (
        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
            <h3 className="text-sm font-semibold text-gray-900">
                Visualisasi Grafik DISC
            </h3>
            <p className="mt-1 text-xs text-gray-500">
                Sebaran nilai Graph I (Most), Graph II (Least), dan Graph III
                (Change/Hasil) pada tiap dimensi, ditampilkan terpisah.
            </p>

            <div className="mt-4 grid gap-6 sm:grid-cols-3">
                {PANELS.map((panel) => {
                    const data = DIMENSIONS.map(({ key, label }) => ({
                        subject: label,
                        value: test[panel.field(key)] ?? null,
                    }));

                    return (
                        <div key={panel.key}>
                            <p
                                className="text-xs font-semibold uppercase tracking-wide"
                                style={{ color: panel.color }}
                            >
                                {panel.title}
                            </p>
                            <div className="mt-2 h-56 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart
                                        data={data}
                                        margin={{
                                            top: 10,
                                            right: 10,
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
                                            tick={{
                                                fill: '#898781',
                                                fontSize: 11,
                                            }}
                                            tickLine={false}
                                            axisLine={false}
                                            width={28}
                                        />
                                        <Tooltip
                                            content={
                                                <PanelTooltip
                                                    color={panel.color}
                                                />
                                            }
                                        />
                                        <Line
                                            type="linear"
                                            dataKey="value"
                                            stroke={panel.color}
                                            strokeWidth={2}
                                            dot={{
                                                r: 4,
                                                fill: panel.color,
                                                stroke: '#ffffff',
                                                strokeWidth: 2,
                                            }}
                                            activeDot={{ r: 6 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
