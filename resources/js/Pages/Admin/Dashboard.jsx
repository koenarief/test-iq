import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link, Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto grid max-w-7xl gap-6 sm:px-6 md:grid-cols-2 lg:px-8">
                    <Link
                        href={route('admin.ist-questions.index')}
                        className="block overflow-hidden bg-white p-6 shadow-sm transition hover:shadow-md sm:rounded-lg"
                    >
                        <h3 className="text-lg font-semibold text-gray-900">
                            Bank Soal IST
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Kelola soal, opsi jawaban, dan kunci jawaban tiap
                            subtes IST yang disajikan ke peserta tes.
                        </p>
                    </Link>

                    <Link
                        href={route('admin.ist-answer-keys.index')}
                        className="block overflow-hidden bg-white p-6 shadow-sm transition hover:shadow-md sm:rounded-lg"
                    >
                        <h3 className="text-lg font-semibold text-gray-900">
                            Kunci Jawaban IST (Skoring)
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Kelola tabel kunci jawaban per nomor soal yang
                            dipakai oleh layanan skoring manual.
                        </p>
                    </Link>

                    <Link
                        href={route('admin.ist-results.index')}
                        className="block overflow-hidden bg-white p-6 shadow-sm transition hover:shadow-md sm:rounded-lg"
                    >
                        <h3 className="text-lg font-semibold text-gray-900">
                            Hasil Tes IST
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Lihat daftar peserta dan hasil Tes Kemampuan
                            Kognitif: skor per subtes, IQ, dan profil
                            dominasi.
                        </p>
                    </Link>

                    <Link
                        href={route('admin.disc-results.index')}
                        className="block overflow-hidden bg-white p-6 shadow-sm transition hover:shadow-md sm:rounded-lg"
                    >
                        <h3 className="text-lg font-semibold text-gray-900">
                            Hasil Tes DISC
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Lihat daftar peserta dan hasil Tes Gaya Kerja:
                            skor D/I/S/C dan tipe profil.
                        </p>
                    </Link>

                    <Link
                        href={route('admin.users.index')}
                        className="block overflow-hidden bg-white p-6 shadow-sm transition hover:shadow-md sm:rounded-lg"
                    >
                        <h3 className="text-lg font-semibold text-gray-900">
                            Manajemen User
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Tambah, ubah, dan hapus akun user yang bisa login
                            ke halaman admin ini.
                        </p>
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
