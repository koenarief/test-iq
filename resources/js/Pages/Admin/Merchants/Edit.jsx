import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import MerchantForm from './Partials/Form';

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

export default function Edit({ merchant, startUrls }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Ubah Merchant — {merchant.name}
                </h2>
            }
        >
            <Head title="Ubah Merchant" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <MerchantForm
                            merchant={merchant}
                            submitUrl={route(
                                'admin.merchants.update',
                                merchant.public_id,
                            )}
                            method="put"
                        />
                    </div>

                    <div className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900">
                                URL Mulai Tes untuk Merchant Ini
                            </h3>
                            <p className="mt-1 text-xs text-gray-500">
                                Bagikan link ini ke peserta dari merchant{' '}
                                {merchant.name}. Hasil tes mereka otomatis
                                dikelompokkan ke merchant ini.
                            </p>
                        </div>

                        <StartUrlRow label="Tes IST" url={startUrls.ist} />
                        <StartUrlRow label="Tes DISC" url={startUrls.disc} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
