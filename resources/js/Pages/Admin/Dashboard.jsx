import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link, Head } from '@inertiajs/react';
import {
    BarChart3,
    Briefcase,
    Building2,
    ClipboardList,
    FileQuestion,
    KeyRound,
    Users,
} from 'lucide-react';

const MENU_ITEMS = [
    {
        href: 'admin.ist-questions.index',
        icon: FileQuestion,
        color: '#1E88E5',
        title: 'Bank Soal IST',
        desc: 'Kelola soal, opsi jawaban, dan kunci jawaban tiap subtes IST yang disajikan ke peserta tes.',
    },
    {
        href: 'admin.ist-answer-keys.index',
        icon: KeyRound,
        color: '#FFC107',
        title: 'Kunci Jawaban IST (Skoring)',
        desc: 'Kelola tabel kunci jawaban per nomor soal yang dipakai oleh layanan skoring manual.',
    },
    {
        href: 'admin.ist-results.index',
        icon: ClipboardList,
        color: '#43A047',
        title: 'Hasil Tes IST',
        desc: 'Lihat daftar peserta dan hasil Tes Kemampuan Kognitif: skor per subtes, IQ, dan profil dominasi.',
    },
    {
        href: 'admin.disc-results.index',
        icon: BarChart3,
        color: '#E53935',
        title: 'Hasil Tes DISC',
        desc: 'Lihat daftar peserta dan hasil Tes Gaya Kerja: skor D/I/S/C dan tipe profil.',
    },
    {
        href: 'admin.competency-results.index',
        icon: Briefcase,
        color: '#3949AB',
        title: 'Hasil Tes Kompetensi',
        desc: 'Lihat daftar peserta dan hasil Tes Kompetensi per divisi: skor tiap subtes dan skor akhir.',
    },
    {
        href: 'admin.users.index',
        icon: Users,
        color: '#8E24AA',
        title: 'Manajemen User',
        desc: 'Tambah, ubah, dan hapus akun user yang bisa login ke halaman admin ini.',
    },
    {
        href: 'admin.merchants.index',
        icon: Building2,
        color: '#00897B',
        title: 'Merchant',
        desc: 'Kelola data merchant dan dapatkan link mulai tes khusus tiap merchant.',
    },
];

const BAND_CLASS = {
    Exceptional: 'bg-emerald-100 text-emerald-700',
    Strong: 'bg-green-100 text-green-700',
    Adequate: 'bg-amber-100 text-amber-700',
    'Development Area': 'bg-orange-100 text-orange-700',
    Weakness: 'bg-red-100 text-red-700',
};

export default function Dashboard({ jobFitResults = [] }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <span className="grid h-9 w-9 place-items-center rounded-xl bg-zinc-900 text-[14px] font-bold text-white">
                        A
                    </span>
                    <div>
                        <h2 className="text-[22px] font-extrabold leading-none tracking-[-0.02em] text-zinc-900">
                            Dashboard
                        </h2>
                        <p className="mt-1 text-[13px] text-zinc-500">
                            Ringkasan menu pengelolaan tes IST & DISC.
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard Admin" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-[20px] border border-zinc-200/80 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.04),0_1px_2px_rgba(0,0,0,0.04)]">
                        <div className="flex items-center justify-between border-b border-zinc-200/80 px-6 py-4">
                            <h3 className="text-[15px] font-semibold tracking-[-0.01em] text-zinc-900">
                                Job Fit Score Kandidat
                            </h3>
                            <Link
                                href={route('admin.assessment-recaps.index')}
                                className="text-[13px] font-medium text-zinc-500 hover:text-zinc-800"
                            >
                                Lihat semua
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-zinc-100 text-sm">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-2.5 text-left font-medium text-zinc-500">
                                            Kandidat
                                        </th>
                                        <th className="px-6 py-2.5 text-left font-medium text-zinc-500">
                                            Posisi
                                        </th>
                                        <th className="px-6 py-2.5 text-left font-medium text-zinc-500">
                                            Job Fit Score
                                        </th>
                                        <th className="px-6 py-2.5 text-left font-medium text-zinc-500">
                                            Kategori
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-100">
                                    {jobFitResults.map((result) => (
                                        <tr key={result.id}>
                                            <td className="px-6 py-3">
                                                <div className="font-medium text-zinc-900">
                                                    {result.candidate_name}
                                                </div>
                                                <div className="text-xs text-zinc-500">
                                                    {result.candidate_email}
                                                </div>
                                            </td>
                                            <td className="px-6 py-3 text-zinc-700">
                                                {result.position_name ?? '—'}
                                            </td>
                                            <td className="px-6 py-3 font-semibold text-zinc-900">
                                                {result.job_fit_score}
                                            </td>
                                            <td className="px-6 py-3">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs ${BAND_CLASS[result.job_fit_band]}`}
                                                >
                                                    {result.job_fit_band}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                    {jobFitResults.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-6 text-center text-zinc-500"
                                            >
                                                Belum ada kandidat dengan Job
                                                Fit Score.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div className="mx-auto grid max-w-7xl gap-5 px-4 pt-5 sm:px-6 md:grid-cols-2 lg:px-8 lg:grid-cols-3">
                    {MENU_ITEMS.map(({ href, icon: Icon, color, title, desc }) => (
                        <Link
                            key={href}
                            href={route(href)}
                            className="group block overflow-hidden rounded-[20px] border border-zinc-200/80 bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.04),0_1px_2px_rgba(0,0,0,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgba(0,0,0,0.07),0_1px_2px_rgba(0,0,0,0.04)]"
                        >
                            <span
                                className="grid h-10 w-10 place-items-center rounded-xl"
                                style={{
                                    backgroundColor: `${color}1F`,
                                    color,
                                }}
                            >
                                <Icon className="h-5 w-5" strokeWidth={2.2} />
                            </span>
                            <h3 className="mt-4 text-[15px] font-semibold tracking-[-0.01em] text-zinc-900">
                                {title}
                            </h3>
                            <p className="mt-1.5 text-[13px] leading-[1.5] text-zinc-500">
                                {desc}
                            </p>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
