// Kartu grafik DISC bergaya "profile.html": satu garis netral yang
// menghubungkan 4 dimensi (D/I/S/C), tiap titik diberi warna sesuai
// dimensinya, dengan pita latar HIGH/LOW dan label nilai langsung di
// atas tiap titik — dipakai terpisah untuk Graph I/II/III.

const DIMENSIONS = [
    { key: 'D', label: 'Dominance', color: '#E53935' },
    { key: 'I', label: 'Influence', color: '#FFC107' },
    { key: 'S', label: 'Steadiness', color: '#43A047' },
    { key: 'C', label: 'Conscientiousness', color: '#1E88E5' },
];

const GRAPHS = [
    {
        id: 'I',
        title: 'Graph I (Most)',
        subtitle: 'Mask / Public Self',
        desc: 'Cara Anda beradaptasi di tempat kerja',
        field: (key) => `most_graph_${key.toLowerCase()}`,
    },
    {
        id: 'II',
        title: 'Graph II (Least)',
        subtitle: 'Core / Private Self',
        desc: 'Naluri alami di bawah tekanan',
        field: (key) => `least_graph_${key.toLowerCase()}`,
    },
    {
        id: 'III',
        title: 'Graph III (Change)',
        subtitle: 'Adapted / Perceived',
        desc: 'Perubahan perilaku yang dipersepsikan',
        field: (key) => `graph_${key.toLowerCase()}`,
    },
];

const WIDTH = 360;
const HEIGHT = 260;
const MARGIN = { top: 36, right: 18, bottom: 36, left: 38 };
const PLOT_W = WIDTH - MARGIN.left - MARGIN.right;
const PLOT_H = HEIGHT - MARGIN.top - MARGIN.bottom;
const TICKS = [0, 25, 50, 75, 100];

const yFor = (value) => MARGIN.top + (1 - value / 100) * PLOT_H;
const xFor = (index) => MARGIN.left + (index / (DIMENSIONS.length - 1)) * PLOT_W;

function DiscLineCard({ graph, test }) {
    const points = DIMENSIONS.map((dim, index) => {
        const value = Number(test[graph.field(dim.key)] ?? 0);

        return { ...dim, value, x: xFor(index), y: yFor(value) };
    });

    const linePath = points
        .map((p, index) => `${index === 0 ? 'M' : 'L'} ${p.x} ${p.y}`)
        .join(' ');

    return (
        <div className="overflow-hidden rounded-[20px] border border-zinc-200/80 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.04),0_1px_2px_rgba(0,0,0,0.04)]">
            <div className="flex items-start justify-between px-6 pb-3 pt-5">
                <div>
                    <div className="flex items-center gap-2">
                        <h3 className="text-[13.5px] font-semibold tracking-[-0.01em] text-zinc-900">
                            {graph.title}
                        </h3>
                        <span className="rounded-full bg-zinc-900 px-2 py-0.5 text-[10px] font-medium tracking-wide text-white">
                            {graph.id}
                        </span>
                    </div>
                    <p className="mt-1 text-[12px] font-medium text-zinc-500">
                        {graph.subtitle}
                    </p>
                </div>
                <p className="max-w-[92px] text-right text-[11px] leading-[1.3] text-zinc-400">
                    {graph.desc}
                </p>
            </div>

            <div className="px-2 pb-1">
                <svg
                    viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                    className="h-auto w-full"
                    role="img"
                    aria-label={`Grafik ${graph.title}`}
                >
                    <clipPath id={`disc-band-clip-${graph.id}`}>
                        <rect
                            x={MARGIN.left}
                            y={MARGIN.top}
                            width={PLOT_W}
                            height={PLOT_H}
                            rx="8"
                        />
                    </clipPath>
                    <g clipPath={`url(#disc-band-clip-${graph.id})`}>
                        <rect
                            x={MARGIN.left}
                            y={MARGIN.top}
                            width={PLOT_W}
                            height={PLOT_H * 0.5}
                            fill="#F8FAFF"
                        />
                        <rect
                            x={MARGIN.left}
                            y={MARGIN.top + PLOT_H * 0.5}
                            width={PLOT_W}
                            height={PLOT_H * 0.5}
                            fill="#FFFDF5"
                        />
                    </g>

                    <text
                        x={MARGIN.left + PLOT_W - 8}
                        y={MARGIN.top + 14}
                        textAnchor="end"
                        fontSize="9"
                        fontWeight="600"
                        fill="#9AA4C8"
                        letterSpacing="0.6"
                    >
                        HIGH &gt;50
                    </text>
                    <text
                        x={MARGIN.left + PLOT_W - 8}
                        y={MARGIN.top + PLOT_H - 8}
                        textAnchor="end"
                        fontSize="9"
                        fontWeight="600"
                        fill="#C9B27D"
                        letterSpacing="0.6"
                    >
                        LOW &lt;50
                    </text>

                    {TICKS.map((tick) => {
                        const y = yFor(tick);
                        const isMid = tick === 50;

                        return (
                            <g key={tick}>
                                <line
                                    x1={MARGIN.left}
                                    x2={MARGIN.left + PLOT_W}
                                    y1={y}
                                    y2={y}
                                    stroke={isMid ? '#1E293B' : '#EDEEF2'}
                                    strokeWidth={isMid ? 1.2 : 1}
                                    strokeDasharray={isMid ? '6 6' : '0'}
                                    opacity={isMid ? 0.9 : 1}
                                />
                                <text
                                    x={MARGIN.left - 10}
                                    y={y + 3}
                                    textAnchor="end"
                                    fontSize="10"
                                    fontWeight="500"
                                    fill="#8B93A8"
                                >
                                    {tick}
                                </text>
                            </g>
                        );
                    })}

                    {points.map((p) => (
                        <line
                            key={`grid-${p.key}`}
                            x1={p.x}
                            x2={p.x}
                            y1={MARGIN.top}
                            y2={MARGIN.top + PLOT_H}
                            stroke="#F0F1F5"
                            strokeWidth="1"
                        />
                    ))}

                    <path
                        d={linePath}
                        fill="none"
                        stroke="#0F172A"
                        strokeWidth="3.2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        opacity="0.12"
                    />
                    <path
                        d={linePath}
                        fill="none"
                        stroke="#0F172A"
                        strokeWidth="2.2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />

                    {points.map((p) => (
                        <g key={`point-${p.key}`}>
                            <circle cx={p.x} cy={p.y} r="12" fill={p.color} opacity="0.18" />
                            <circle
                                cx={p.x}
                                cy={p.y}
                                r="7.5"
                                fill="white"
                                stroke={p.color}
                                strokeWidth="2.4"
                            />
                            <circle cx={p.x} cy={p.y} r="3.2" fill={p.color} />
                            <rect
                                x={p.x - 16}
                                y={p.y - 32}
                                width="32"
                                height="16"
                                rx="8"
                                fill="#111827"
                            />
                            <text
                                x={p.x}
                                y={p.y - 21}
                                textAnchor="middle"
                                fontSize="10"
                                fontWeight="700"
                                fill="white"
                            >
                                {p.value}
                            </text>
                        </g>
                    ))}

                    {points.map((p) => (
                        <text
                            key={`label-${p.key}`}
                            x={p.x}
                            y={252}
                            textAnchor="middle"
                            fontSize="13"
                            fontWeight="800"
                            fill={p.color}
                            letterSpacing="-0.02em"
                        >
                            {p.key}
                        </text>
                    ))}
                </svg>
            </div>

            <div className="flex gap-3 px-6 pb-4 pt-1">
                {points.map((p) => (
                    <div key={p.key} className="flex items-center gap-1.5">
                        <span
                            className="h-2.5 w-2.5 rounded-full"
                            style={{ background: p.color }}
                        />
                        <span className="text-[11px] font-medium text-zinc-600">
                            {p.key}
                        </span>
                        <span className="text-[11px] font-semibold text-zinc-900">
                            {p.value}
                        </span>
                    </div>
                ))}
            </div>
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
                Graph I (Most), Graph II (Least), dan Graph III
                (Change/Hasil) pada tiap dimensi D/I/S/C.
            </p>

            <div className="mt-4 grid gap-6 lg:grid-cols-3">
                {GRAPHS.map((graph) => (
                    <DiscLineCard key={graph.id} graph={graph} test={test} />
                ))}
            </div>
        </div>
    );
}
