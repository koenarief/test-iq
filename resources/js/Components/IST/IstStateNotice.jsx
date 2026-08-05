import { AlertCircle, CheckCircle2, Info } from 'lucide-react';

const styles = {
    error: {
        container: 'border-red-500/30 bg-red-500/10 text-red-200',
        icon: AlertCircle,
        iconClass: 'text-red-400',
    },
    success: {
        container: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200',
        icon: CheckCircle2,
        iconClass: 'text-emerald-400',
    },
    info: {
        container: 'border-blue-500/30 bg-blue-500/10 text-blue-100',
        icon: Info,
        iconClass: 'text-blue-400',
    },
};

export default function IstStateNotice({ children, title, tone = 'info' }) {
    const selectedStyle = styles[tone] ?? styles.info;
    const Icon = selectedStyle.icon;

    return (
        <div
            className={`flex items-start gap-3 rounded-xl border p-4 ${selectedStyle.container}`}
            role={tone === 'error' ? 'alert' : 'status'}
        >
            <Icon className={`mt-0.5 h-5 w-5 shrink-0 ${selectedStyle.iconClass}`} aria-hidden="true" />
            <div className="min-w-0 text-sm leading-relaxed">
                {title && <p className="mb-1 font-semibold text-white">{title}</p>}
                <div>{children}</div>
            </div>
        </div>
    );
}
