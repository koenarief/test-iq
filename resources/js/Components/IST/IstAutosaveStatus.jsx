import {
    AlertCircle,
    CheckCircle2,
    CloudOff,
    CloudUpload,
    LoaderCircle,
    RefreshCw,
} from 'lucide-react';
import { useEffect, useRef } from 'react';

const states = {
    idle: { label: 'Belum ada perubahan', icon: CloudUpload, className: 'border-zinc-700 bg-zinc-950/70 text-zinc-300' },
    dirty: { label: 'Perubahan belum tersimpan', icon: CloudUpload, className: 'border-amber-500/30 bg-amber-500/10 text-amber-200' },
    saving: { label: 'Menyimpan…', icon: LoaderCircle, className: 'border-blue-500/30 bg-blue-500/10 text-blue-200', spin: true },
    saved: { label: 'Tersimpan', icon: CheckCircle2, className: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' },
    retrying: { label: 'Koneksi bermasalah, mencoba kembali', icon: RefreshCw, className: 'border-amber-500/30 bg-amber-500/10 text-amber-200', spin: true },
    error: { label: 'Gagal menyimpan', icon: CloudOff, className: 'border-red-500/30 bg-red-500/10 text-red-200' },
    conflict: { label: 'Konflik data, muat ulang halaman', icon: AlertCircle, className: 'border-red-500/30 bg-red-500/10 text-red-200' },
    expired: { label: 'Waktu habis', icon: AlertCircle, className: 'border-red-500/30 bg-red-500/10 text-red-200' },
};

function savedTime(value) {
    if (!value) return null;

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? null
        : new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(date);
}

export default function IstAutosaveStatus({ status = 'idle', lastSavedAt, onRetry, onReload }) {
    const selected = states[status] ?? states.idle;
    const Icon = selected.icon;
    const noticeRef = useRef(null);
    const time = savedTime(lastSavedAt);

    useEffect(() => {
        if (status === 'conflict') {
            noticeRef.current?.focus();
        }
    }, [status]);

    return (
        <div
            ref={noticeRef}
            tabIndex={status === 'conflict' ? -1 : undefined}
            className={`flex min-h-11 max-w-full flex-wrap items-center gap-2 rounded-xl border px-3.5 py-2 text-xs ${selected.className}`}
            aria-live="polite"
            role={status === 'conflict' || status === 'error' ? 'alert' : 'status'}
        >
            <Icon className={`h-4 w-4 shrink-0 ${selected.spin ? 'animate-spin motion-reduce:animate-none' : ''}`} aria-hidden="true" />
            <span>{selected.label}</span>
            {status === 'saved' && time && <span className="text-[10px] opacity-70">({time})</span>}
            {status === 'error' && onRetry && (
                <button
                    type="button"
                    onClick={onRetry}
                    className="ml-auto min-h-8 rounded-lg border border-current/30 px-2 font-semibold hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                >
                    Coba lagi
                </button>
            )}
            {status === 'conflict' && onReload && (
                <button
                    type="button"
                    onClick={onReload}
                    className="ml-auto min-h-8 rounded-lg border border-current/30 px-2 font-semibold hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                >
                    Muat Ulang
                </button>
            )}
        </div>
    );
}
