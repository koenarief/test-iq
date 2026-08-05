import { AlertTriangle, Clock3 } from 'lucide-react';

function fallbackFormat(value) {
    const seconds = Math.max(0, Math.ceil(Number(value) || 0));
    const minutes = Math.floor(seconds / 60);

    return `${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
}

export default function IstCountdown({
    remainingSeconds = 0,
    formattedTime = null,
    label = 'Sisa waktu',
    isExpired = false,
    hasValidDeadline = true,
}) {
    const urgent = !isExpired && remainingSeconds > 0 && remainingSeconds <= 60;
    const display = formattedTime ?? fallbackFormat(remainingSeconds);

    return (
        <div
            className={`inline-flex min-h-11 items-center gap-2 rounded-xl border px-3.5 py-2 font-mono text-sm font-semibold ${
                !hasValidDeadline || isExpired
                    ? 'border-red-500/40 bg-red-500/10 text-red-300'
                    : urgent
                      ? 'border-amber-500/40 bg-amber-500/10 text-amber-200'
                      : 'border-zinc-700 bg-zinc-950/80 text-zinc-100'
            }`}
            aria-label={`${label}: ${display}`}
        >
            {!hasValidDeadline ? (
                <AlertTriangle className="h-4 w-4 text-red-400" aria-hidden="true" />
            ) : (
                <Clock3 className={`h-4 w-4 ${urgent ? 'text-amber-400' : 'text-blue-400'}`} aria-hidden="true" />
            )}
            <span aria-hidden="true">{display}</span>
        </div>
    );
}
