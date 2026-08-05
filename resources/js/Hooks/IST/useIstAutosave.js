import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

const MAX_BATCH_SIZE = 20;
const RETRY_DELAYS = [1000, 2000, 4000];

function safeQuestionId(change) {
    const questionId = Number(change?.ist_test_question_id);

    return Number.isInteger(questionId) && questionId > 0 ? questionId : null;
}

function responseIds(response, key) {
    return Array.isArray(response?.data?.[key])
        ? response.data[key].map(Number).filter(Number.isInteger)
        : [];
}

function errorStatus(error) {
    return Number(error?.response?.status) || null;
}

export default function useIstAutosave({
    autosaveUrl,
    enabled = false,
    expired = false,
    resetKey = '',
    debounceMs = 500,
    onPersisted,
    onConflict,
    onSessionUnavailable,
    onValidationError,
}) {
    const queueRef = useRef(new Map());
    const debounceTimerRef = useRef(null);
    const retryTimerRef = useRef(null);
    const retryResolverRef = useRef(null);
    const activeControllerRef = useRef(null);
    const drainPromiseRef = useRef(null);
    const stoppedRef = useRef(false);
    const enabledRef = useRef(enabled);
    const expiredRef = useRef(expired);
    const urlRef = useRef(autosaveUrl);
    const callbacksRef = useRef({ onPersisted, onConflict, onSessionUnavailable, onValidationError });

    const [status, setStatus] = useState('idle');
    const [hasPendingChanges, setHasPendingChanges] = useState(false);
    const [lastSavedAt, setLastSavedAt] = useState(null);
    const [conflict, setConflict] = useState(null);
    const [networkError, setNetworkError] = useState(null);

    callbacksRef.current = { onPersisted, onConflict, onSessionUnavailable, onValidationError };
    enabledRef.current = enabled;
    expiredRef.current = expired;
    urlRef.current = autosaveUrl;

    const syncPendingState = useCallback(() => {
        setHasPendingChanges(queueRef.current.size > 0 || drainPromiseRef.current !== null);
    }, []);

    const clearScheduledWork = useCallback(() => {
        if (debounceTimerRef.current !== null) {
            window.clearTimeout(debounceTimerRef.current);
            debounceTimerRef.current = null;
        }

        if (retryTimerRef.current !== null) {
            window.clearTimeout(retryTimerRef.current);
            retryTimerRef.current = null;
            retryResolverRef.current?.();
            retryResolverRef.current = null;
        }
    }, []);

    const waitForRetry = useCallback((delay) => new Promise((resolve) => {
        retryResolverRef.current = resolve;
        retryTimerRef.current = window.setTimeout(() => {
            retryTimerRef.current = null;
            retryResolverRef.current = null;
            resolve();
        }, delay);
    }), []);

    const requeueBatch = useCallback((batch) => {
        batch.forEach((change) => {
            const questionId = safeQuestionId(change);
            const queued = questionId === null ? null : queueRef.current.get(questionId);

            if (questionId !== null && (!queued || queued.client_revision <= change.client_revision)) {
                queueRef.current.set(questionId, change);
            }
        });
        syncPendingState();
    }, [syncPendingState]);

    const drainQueue = useCallback(() => {
        if (drainPromiseRef.current !== null) {
            return drainPromiseRef.current;
        }

        const drain = async () => {
            let result = { ok: true, reason: null };

            while (
                queueRef.current.size > 0
                && !stoppedRef.current
                && enabledRef.current
                && !expiredRef.current
                && urlRef.current
            ) {
                const batch = [...queueRef.current.values()]
                    .sort((left, right) => left.ist_test_question_id - right.ist_test_question_id)
                    .slice(0, MAX_BATCH_SIZE);

                batch.forEach((change) => queueRef.current.delete(change.ist_test_question_id));
                syncPendingState();

                let response = null;
                let failure = null;

                for (let attempt = 0; attempt <= RETRY_DELAYS.length; attempt += 1) {
                    if (stoppedRef.current || expiredRef.current || !enabledRef.current) {
                        failure = { reason: 'stopped' };
                        break;
                    }

                    setStatus(attempt === 0 ? 'saving' : 'retrying');
                    setNetworkError(attempt === 0 ? null : 'Koneksi bermasalah, mencoba kembali.');
                    activeControllerRef.current = new AbortController();

                    try {
                        response = await axios.put(
                            urlRef.current,
                            { changes: batch },
                            {
                                signal: activeControllerRef.current.signal,
                                headers: { Accept: 'application/json' },
                            },
                        );
                        activeControllerRef.current = null;
                        break;
                    } catch (error) {
                        activeControllerRef.current = null;

                        if (axios.isCancel(error) || error?.code === 'ERR_CANCELED') {
                            failure = { reason: 'stopped' };
                            break;
                        }

                        const statusCode = errorStatus(error);
                        const responseCode = error?.response?.data?.code ?? null;

                        if (statusCode === 409) {
                            const conflictType = responseCode === 'IST_ANSWER_REVISION_CONFLICT'
                                ? 'revision'
                                : 'state';
                            const conflictState = { type: conflictType, code: responseCode };

                            stoppedRef.current = true;
                            setConflict(conflictState);
                            setStatus('conflict');
                            callbacksRef.current.onConflict?.(conflictState);
                            failure = { reason: 'conflict', conflict: conflictState };
                            break;
                        }

                        if (statusCode === 404) {
                            stoppedRef.current = true;
                            setStatus('error');
                            callbacksRef.current.onSessionUnavailable?.();
                            failure = { reason: 'session' };
                            break;
                        }

                        if (statusCode === 422) {
                            stoppedRef.current = true;
                            setStatus('error');
                            callbacksRef.current.onValidationError?.();
                            failure = { reason: 'validation' };
                            break;
                        }

                        const retryable = statusCode === null || statusCode >= 500;

                        if (!retryable || attempt === RETRY_DELAYS.length) {
                            setStatus('error');
                            setNetworkError('Jawaban belum tersimpan karena gangguan koneksi.');
                            failure = { reason: 'network' };
                            break;
                        }

                        await waitForRetry(RETRY_DELAYS[attempt]);
                    }
                }

                if (!response) {
                    if (failure?.reason === 'network') {
                        requeueBatch(batch);
                    }

                    result = { ok: false, reason: failure?.reason ?? 'error', conflict: failure?.conflict ?? null };
                    break;
                }

                const savedIds = responseIds(response, 'savedQuestionIds');
                const idempotentIds = responseIds(response, 'idempotentQuestionIds');
                const staleIds = responseIds(response, 'ignoredStaleQuestionIds');
                const acceptedIds = [...new Set([...savedIds, ...idempotentIds])];
                const savedAt = response?.data?.savedAt ?? new Date().toISOString();

                callbacksRef.current.onPersisted?.(batch, acceptedIds, savedAt);
                setLastSavedAt(savedAt);
                setNetworkError(null);

                if (staleIds.length > 0) {
                    const staleConflict = { type: 'stale', questionIds: staleIds };

                    stoppedRef.current = true;
                    setConflict(staleConflict);
                    setStatus('conflict');
                    callbacksRef.current.onConflict?.(staleConflict);
                    result = { ok: false, reason: 'conflict', conflict: staleConflict };
                    break;
                }
            }

            return result;
        };

        drainPromiseRef.current = drain().finally(() => {
            drainPromiseRef.current = null;
            syncPendingState();

            if (!stoppedRef.current && !expiredRef.current && queueRef.current.size === 0) {
                setStatus((current) => ['saving', 'retrying', 'dirty'].includes(current) ? 'saved' : current);
            }
        });
        syncPendingState();

        return drainPromiseRef.current;
    }, [requeueBatch, syncPendingState, waitForRetry]);

    const scheduleDrain = useCallback(() => {
        if (debounceTimerRef.current !== null) {
            window.clearTimeout(debounceTimerRef.current);
        }

        const delay = Math.min(700, Math.max(400, Number(debounceMs) || 500));
        debounceTimerRef.current = window.setTimeout(() => {
            debounceTimerRef.current = null;
            void drainQueue();
        }, delay);
    }, [debounceMs, drainQueue]);

    const enqueue = useCallback((change) => {
        const questionId = safeQuestionId(change);

        if (
            questionId === null
            || !enabledRef.current
            || expiredRef.current
            || stoppedRef.current
            || !urlRef.current
        ) {
            return false;
        }

        queueRef.current.set(questionId, change);
        setStatus('dirty');
        setNetworkError(null);
        syncPendingState();
        scheduleDrain();

        return true;
    }, [scheduleDrain, syncPendingState]);

    const flush = useCallback(async () => {
        if (debounceTimerRef.current !== null) {
            window.clearTimeout(debounceTimerRef.current);
            debounceTimerRef.current = null;
        }

        if (conflict) {
            return { ok: false, reason: 'conflict', conflict };
        }

        return drainQueue();
    }, [conflict, drainQueue]);

    const stop = useCallback(({ nextStatus = null, clearQueue = false } = {}) => {
        stoppedRef.current = true;
        clearScheduledWork();
        activeControllerRef.current?.abort();
        activeControllerRef.current = null;

        if (clearQueue) {
            queueRef.current.clear();
        }

        if (nextStatus) {
            setStatus(nextStatus);
        }

        syncPendingState();
    }, [clearScheduledWork, syncPendingState]);

    const retry = useCallback(() => {
        if (conflict || expiredRef.current || !enabledRef.current) {
            return Promise.resolve({ ok: false, reason: conflict ? 'conflict' : 'stopped' });
        }

        stoppedRef.current = false;
        setNetworkError(null);

        return drainQueue();
    }, [conflict, drainQueue]);

    useEffect(() => {
        clearScheduledWork();
        activeControllerRef.current?.abort();
        activeControllerRef.current = null;
        queueRef.current.clear();
        stoppedRef.current = false;
        setStatus(expired ? 'expired' : 'idle');
        setHasPendingChanges(false);
        setLastSavedAt(null);
        setConflict(null);
        setNetworkError(null);
    }, [clearScheduledWork, expired, resetKey]);

    useEffect(() => {
        if (expired) {
            stop({ nextStatus: 'expired', clearQueue: true });
        } else if (!enabled) {
            stop({ clearQueue: false });
        } else if (!conflict) {
            stoppedRef.current = false;
        }
    }, [conflict, enabled, expired, stop]);

    useEffect(() => () => {
        stoppedRef.current = true;
        clearScheduledWork();
        activeControllerRef.current?.abort();
    }, [clearScheduledWork]);

    return {
        status,
        enqueue,
        flush,
        stop,
        retry,
        hasPendingChanges,
        lastSavedAt,
        conflict,
        networkError,
    };
}
