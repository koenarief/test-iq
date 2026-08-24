import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    ReferenceLine,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const SUBTEST_ORDER = [
    'SE',
    'WA',
    'AN',
    'GE',
    'RA',
    'ZR',
    'FA',
    'WU',
    'ME',
];

const CHART_DOMAIN = [55, 145];
const CHART_TICKS = [55, 70, 85, 100, 115, 130, 145];

function clampToDomain(value) {
    if (
        value === null
        || value === ''
        || typeof value === 'boolean'
    ) {
        return null;
    }

    const numeric = Number(value);

    return Number.isFinite(numeric)
        ? Math.min(
            CHART_DOMAIN[1],
            Math.max(CHART_DOMAIN[0], numeric),
        )
        : null;
}

function formatNumber(value) {
    if (
        value === null
        || value === ''
        || typeof value === 'boolean'
    ) {
        return '—';
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return '—';
    }

    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(numeric);
}

function ChartTooltip({ active, payload }) {
    if (!active || !payload?.length) {
        return null;
    }

    const point = payload[0]?.payload;

    return (
        <div className="rounded-xl border border-zinc-700 bg-zinc-900/95 px-3 py-2 text-xs shadow-xl">
            <p className="font-semibold text-white">
                <span className="font-mono text-blue-300">
                    {point?.code ?? '—'}
                </span>

                {point?.name ? ` — ${point.name}` : ''}
            </p>

            <dl className="mt-2 space-y-1 text-zinc-300">
                <div className="flex justify-between gap-5">
                    <dt>Standard score (SW)</dt>

                    <dd className="font-semibold text-blue-300">
                        {formatNumber(point?.standardScore)}
                    </dd>
                </div>

                <div className="flex justify-between gap-5">
                    <dt>Raw score (RW)</dt>

                    <dd>{formatNumber(point?.rawScore)}</dd>
                </div>
            </dl>

            {point?.standardScore === null && (
                <p className="mt-2 max-w-56 text-amber-300">
                    Data norma usia untuk subtes ini belum tersedia.
                </p>
            )}
        </div>
    );
}

function buildSubtestData(graphPoints, subtests) {
    const graphByCode = new Map(
        graphPoints.map((point) => [point?.code, point]),
    );

    const subtestByCode = new Map(
        subtests.map((subtest) => [subtest?.code, subtest]),
    );

    return SUBTEST_ORDER.map((code) => {
        const graphPoint = graphByCode.get(code);
        const subtest = subtestByCode.get(code);

        const standardScore =
            subtest?.standardScore
            ?? graphPoint?.standardScore
            ?? null;

        return {
            code,
            name: subtest?.name ?? null,
            standardScore,
            rawScore: subtest?.rawScore ?? null,
            visualStandardScore: clampToDomain(standardScore),
        };
    });
}

export default function IstResultChart({
    graphPoints = [],
    subtests = [],
    labelledBy = 'ist-result-chart-title ist-result-chart-description',
}) {
    const safeGraphPoints = Array.isArray(graphPoints) ? graphPoints : [];
    const safeSubtests = Array.isArray(subtests) ? subtests : [];

    const data = buildSubtestData(safeGraphPoints, safeSubtests);

    const hasSourceData =
        safeGraphPoints.length > 0 || safeSubtests.length > 0;

    if (!hasSourceData) {
        return (
            <div className="flex h-72 items-center justify-center rounded-xl border border-zinc-800 bg-zinc-950/60 text-sm text-zinc-500">
                Data grafik belum tersedia.
            </div>
        );
    }

    return (
        <div
            className="h-80 w-full sm:h-96"
            role="img"
            aria-labelledby={labelledBy}
        >
            <ResponsiveContainer width="100%" height="100%">
                <LineChart
                    data={data}
                    margin={{
                        top: 12,
                        right: 18,
                        left: 0,
                        bottom: 8,
                    }}
                >
                    <CartesianGrid
                        stroke="#3f3f46"
                        strokeDasharray="3 3"
                        vertical={false}
                    />

                    <XAxis
                        dataKey="code"
                        tick={{
                            fill: '#d4d4d8',
                            fontSize: 12,
                            fontWeight: 700,
                        }}
                        tickLine={false}
                        axisLine={{ stroke: '#52525b' }}
                    />

                    <YAxis
                        domain={CHART_DOMAIN}
                        ticks={CHART_TICKS}
                        tick={{
                            fill: '#a1a1aa',
                            fontSize: 11,
                        }}
                        tickLine={false}
                        axisLine={false}
                        width={36}
                    />

                    <ReferenceLine
                        y={100}
                        stroke="#52525b"
                        strokeDasharray="4 4"
                    />

                    <Tooltip content={<ChartTooltip />} />

                    <Line
                        name="Standard score subtes"
                        type="linear"
                        dataKey="visualStandardScore"
                        stroke="#60a5fa"
                        strokeWidth={3}
                        dot={{
                            r: 5,
                            fill: '#18181b',
                            stroke: '#60a5fa',
                            strokeWidth: 3,
                        }}
                        activeDot={{
                            r: 7,
                            fill: '#60a5fa',
                            stroke: '#dbeafe',
                            strokeWidth: 2,
                        }}
                        connectNulls={false}
                    />
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
