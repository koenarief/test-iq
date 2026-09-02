import MerchantLayout from '@/Layouts/MerchantLayout';
import { Head, Link } from '@inertiajs/react';

function StartUrlRow({ label, url }) {
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </p>
            <div className="mt-1 flex items-stretch gap-2">
                <input
                    readOnly
                    value={url}
                    onFocus={(e) => e.target.select()}
                    className="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-700 shadow-sm"
                />
                <a
                    href={url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Buka
                </a>
            </div>
        </div>
    );
}

export default function Dashboard({ stats, startUrls }) {
    return (
        <MerchantLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard Merchant
                </h2>
            }
        >
            <Head title="Dashboard Merchant" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Total Peserta IST
                            </p>
                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {stats.ist_total}
                            </p>
                        </div>
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                IST Selesai
                            </p>
                            <p className="mt-1 text-3xl font-bold text-green-700">
                                {stats.ist_completed}
                            </p>
                        </div>
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                Total Peserta DISC
                            </p>
                            <p className="mt-1 text-3xl font-bold text-gray-900">
                                {stats.disc_total}
                            </p>
                        </div>
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                DISC Selesai
                            </p>
                            <p className="mt-1 text-3xl font-bold text-green-700">
                                {stats.disc_completed}
                            </p>
                        </div>
                    </div>

                    <div className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900">
                                Link Tes untuk Peserta
                            </h3>
                            <p className="mt-1 text-xs text-gray-500">
                                Bagikan link ini ke peserta Anda. Hasil tes
                                mereka otomatis masuk ke dashboard ini.
                            </p>
                        </div>

                        <StartUrlRow label="Tes IST" url={startUrls.ist} />
                        <StartUrlRow label="Tes DISC" url={startUrls.disc} />
                    </div>

                    <div className="flex flex-wrap gap-4">
                        <Link
                            href={route('merchant.ist-results.index')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                        >
                            Lihat Hasil IST
                        </Link>
                        <Link
                            href={route('merchant.disc-results.index')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                        >
                            Lihat Hasil DISC
                        </Link>
                    </div>
                </div>
            </div>
        </MerchantLayout>
    );
}
