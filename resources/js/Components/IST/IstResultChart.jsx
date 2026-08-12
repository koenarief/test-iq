import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
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

const AREA_ORDER = [
    'verbal',
    'numeric',
    'figural',
    'memory',
];

const AREA_LABELS = {
    verbal: 'Verbal',
    numeric: 'Numerik',
    figural: 'Figural',
    memory: 'Memori',
};

function clampPercentage(value) {
    if (
        value === null
        || value === ''
        || typeof value === 'boolean'
    ) {
        return null;
    }

    const numeric = Number(value);

    return Number.isFinite(numeric)
        ? Math.min(100, Math.max(0, numeric))
        : null;
}

function formatNumber(
    value,
    maximumFractionDigits = 3,
) {
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
        maximumFractionDigits,
    }).format(numeric);
}

function formatPercentage(value) {
    const formatted = formatNumber(value);

    return formatted === '—'
        ? formatted
        : `${formatted}%`;
}

function ChartTooltip({
    active,
    payload,
    mode,
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const point = payload[0]?.payload;

    return (
        <div className="rounded-xl border border-zinc-700 bg-zinc-900/95 px-3 py-2 text-xs shadow-xl">
            <p className="font-semibold text-white">
                {mode === 'area' ? (
                    point?.label ?? '—'
                ) : (
                    <>
                        <span className="font-mono text-blue-300">
                            {point?.code ?? '—'}
                        </span>

                        {point?.name
                            ? ` — ${point.name}`
                            : ''}
                    </>
                )}
            </p>

            <dl className="mt-2 space-y-1 text-zinc-300">
                <div className="flex justify-between gap-5">
                    <dt>Persentase</dt>

                    <dd className="font-semibold text-blue-300">
                        {formatPercentage(
                            point?.percentage
                        )}
                    </dd>
                </div>

                {mode === 'subtest' && (
                    <div className="flex justify-between gap-5">
                        <dt>Skor</dt>

                        <dd>
                            {formatNumber(
                                point?.awardedScore
                            )}
                            {' / '}
                            {formatNumber(
                                point?.maxScore
                            )}
                        </dd>
                    </div>
                )}
            </dl>

            {point?.isOutsideRange && (
                <p className="mt-2 max-w-56 text-amber-300">
                    Nilai sumber di luar rentang 0–100;
                    posisi visual dibatasi pada rentang
                    grafik.
                </p>
            )}
        </div>
    );
}

function buildSubtestData(
    graphPoints,
    subtests,
) {
    const graphByCode = new Map(
        graphPoints.map(
            (point) => [
                point?.code,
                point,
            ],
        ),
    );

    const subtestByCode = new Map(
        subtests.map(
            (subtest) => [
                subtest?.code,
                subtest,
            ],
        ),
    );

    return SUBTEST_ORDER.map((code) => {
        const graphPoint =
            graphByCode.get(code);

        const subtest =
            subtestByCode.get(code);

        const percentage =
            subtest?.percentage
            ?? graphPoint?.percentage
            ?? null;

        const numericPercentage =
            percentage === null
            || percentage === ''
                ? null
                : Number(percentage);

        return {
            code,
            label: code,
            name:
                subtest?.name
                ?? graphPoint?.name
                ?? null,

            percentage:
                Number.isFinite(
                    numericPercentage
                )
                    ? numericPercentage
                    : null,

            visualPercentage:
                clampPercentage(
                    percentage
                ),

            awardedScore:
                subtest?.awardedScore
                ?? graphPoint?.awardedScore
                ?? null,

            maxScore:
                subtest?.maxScore
                ?? graphPoint?.maxScore
                ?? null,

            isOutsideRange:
                Number.isFinite(
                    numericPercentage
                )
                && (
                    numericPercentage < 0
                    || numericPercentage > 100
                ),
        };
    });
}

function buildAreaData(
    areaGraphPoints,
) {
    const areaByKey = new Map(
        areaGraphPoints.map(
            (point) => [
                point?.key,
                point,
            ],
        ),
    );

    return AREA_ORDER.map((key) => {
        const point =
            areaByKey.get(key);

        const percentage =
            point?.percentage
            ?? null;

        const numericPercentage =
            percentage === null
            || percentage === ''
                ? null
                : Number(percentage);

        return {
            key,
            code: key,
            label:
                point?.label
                ?? AREA_LABELS[key],

            percentage:
                Number.isFinite(
                    numericPercentage
                )
                    ? numericPercentage
                    : null,

            visualPercentage:
                clampPercentage(
                    percentage
                ),

            awardedScore: null,
            maxScore: null,

            isOutsideRange:
                Number.isFinite(
                    numericPercentage
                )
                && (
                    numericPercentage < 0
                    || numericPercentage > 100
                ),
        };
    });
}

export default function IstResultChart({
    graphPoints = [],
    subtests = [],
    areaGraphPoints = [],
    mode = 'subtest',
    labelledBy = 'ist-result-chart-title ist-result-chart-description',
}) {
    const safeGraphPoints =
        Array.isArray(graphPoints)
            ? graphPoints
            : [];

    const safeSubtests =
        Array.isArray(subtests)
            ? subtests
            : [];

    const safeAreaGraphPoints =
        Array.isArray(areaGraphPoints)
            ? areaGraphPoints
            : [];

    const isAreaMode =
        mode === 'area';

    const data = isAreaMode
        ? buildAreaData(
            safeAreaGraphPoints
        )
        : buildSubtestData(
            safeGraphPoints,
            safeSubtests
        );

    const hasSourceData =
        isAreaMode
            ? safeAreaGraphPoints.length > 0
            : (
                safeGraphPoints.length > 0
                || safeSubtests.length > 0
            );

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
            <ResponsiveContainer
                width="100%"
                height="100%"
            >
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
                        dataKey={
                            isAreaMode
                                ? 'label'
                                : 'code'
                        }
                        tick={{
                            fill: '#d4d4d8',
                            fontSize: 12,
                            fontWeight: 700,
                        }}
                        tickLine={false}
                        axisLine={{
                            stroke: '#52525b',
                        }}
                    />

                    <YAxis
                        domain={[0, 100]}
                        ticks={[
                            0,
                            20,
                            40,
                            60,
                            80,
                            100,
                        ]}
                        tickFormatter={
                            (value) =>
                                `${value}%`
                        }
                        tick={{
                            fill: '#a1a1aa',
                            fontSize: 11,
                        }}
                        tickLine={false}
                        axisLine={false}
                        width={44}
                    />

                    <Tooltip
                        content={
                            <ChartTooltip
                                mode={
                                    isAreaMode
                                        ? 'area'
                                        : 'subtest'
                                }
                            />
                        }
                    />

                    <Line
                        name={
                            isAreaMode
                                ? 'Skor area'
                                : 'Persentase subtes'
                        }
                        type="linear"
                        dataKey="visualPercentage"
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