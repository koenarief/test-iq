import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

function parseTimestamp(value) {
    if (!value) {
        return null;
    }

    const timestamp = Date.parse(value);

    return Number.isFinite(timestamp) ? timestamp : null;
}

function normalizeFallback(value) {
    const seconds = Number(value);

    return Number.isFinite(seconds) ? Math.max(0, Math.ceil(seconds)) : 0;
}

function formatSeconds(value) {
    const seconds = Math.max(0, Math.ceil(Number(value) || 0));
    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
}

export default function useServerCountdown({
    serverTime,
    phaseEndsAt,
    fallbackSeconds = 0,
    intervalMs = 500,
}) {
    const parsedServerTime = useMemo(() => parseTimestamp(serverTime), [serverTime]);
    const parsedPhaseEndsAt = useMemo(() => parseTimestamp(phaseEndsAt), [phaseEndsAt]);
    const serverOffsetRef = useRef(0);
    const hasValidDeadline = parsedServerTime !== null && parsedPhaseEndsAt !== null;

    const calculateRemaining = useCallback(() => {
        if (!hasValidDeadline) {
            return normalizeFallback(fallbackSeconds);
        }

        const effectiveServerNow = Date.now() + serverOffsetRef.current;
        const remainingMilliseconds = parsedPhaseEndsAt - effectiveServerNow;

        return Math.max(0, Math.ceil(remainingMilliseconds / 1000));
    }, [fallbackSeconds, hasValidDeadline, parsedPhaseEndsAt]);

    const [remainingSeconds, setRemainingSeconds] = useState(() => normalizeFallback(fallbackSeconds));

    useEffect(() => {
        serverOffsetRef.current = parsedServerTime === null ? 0 : parsedServerTime - Date.now();
        setRemainingSeconds(calculateRemaining());
    }, [calculateRemaining, parsedServerTime]);

    useEffect(() => {
        if (!hasValidDeadline) {
            return undefined;
        }

        const recalculate = () => setRemainingSeconds(calculateRemaining());
        const safeInterval = Math.min(1000, Math.max(250, Number(intervalMs) || 500));
        const interval = window.setInterval(recalculate, safeInterval);

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                recalculate();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [calculateRemaining, hasValidDeadline, intervalMs]);

    return {
        remainingSeconds,
        isExpired: hasValidDeadline ? remainingSeconds <= 0 : false,
        formattedTime: formatSeconds(remainingSeconds),
        phaseEndsAt,
        hasValidDeadline,
    };
}
