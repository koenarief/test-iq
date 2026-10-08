import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import CandidateLayout from '@/Layouts/CandidateLayout';
import { Head, Link } from '@inertiajs/react';

const STATUS_LABEL = {
    not_started: 'Belum Dimulai',
    in_progress: 'Sedang Berjalan',
    completed: 'Selesai',
};

const STATUS_CLASS = {
    not_started: 'bg-gray-100 text-gray-600',
    in_progress: 'bg-amber-100 text-amber-700',
    completed: 'bg-green-100 text-green-700',
};

function TestCard({ title, description, test }) {
    const url =
        test.status === 'not_started' ? test.startUrl : test.resumeUrl;

    const buttonLabel =
        test.status === 'completed'
            ? 'Selesai'
            : test.status === 'in_progress'
              ? 'Lanjutkan'
              : 'Mulai';

    return (
        <div className="flex flex-col justify-between gap-4 rounded-lg bg-white p-6 shadow-sm">
            <div>
                <div className="mb-2 flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-900">
                        {title}
                    </h3>
                    <span
                        className={`rounded-full px-3 py-1 text-xs font-medium ${STATUS_CLASS[test.status]}`}
                    >
                        {STATUS_LABEL[test.status]}
                    </span>
                </div>
                <p className="text-sm text-gray-500">{description}</p>
            </div>

            {test.status === 'completed' ? (
                <SecondaryButton disabled className="justify-center">
                    Selesai
                </SecondaryButton>
            ) : (
                <Link href={url}>
                    <PrimaryButton className="w-full justify-center">
                        {buttonLabel}
                    </PrimaryButton>
                </Link>
            )}
        </div>
    );
}

export default function Dashboard({ position, allCompleted, tests }) {
    return (
        <CandidateLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard Kandidat
                </h2>
            }
        >
            <Head title="Dashboard Kandidat" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    {position && (
                        <div className="rounded-lg bg-white p-4 text-sm text-gray-600 shadow-sm">
                            Posisi yang dilamar:{' '}
                            <span className="font-semibold text-gray-900">
                                {position.name}
                            </span>
                        </div>
                    )}

                    {allCompleted ? (
                        <div className="rounded-lg bg-green-50 p-6 text-center text-green-800 shadow-sm">
                            <p className="text-lg font-semibold">
                                Terima kasih telah menyelesaikan seluruh tes.
                            </p>
                            <p className="mt-1 text-sm">
                                Hasil Anda akan diproses oleh tim HR kami.
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-3">
                            <TestCard
                                title="DISC"
                                description="Tes gaya kerja dan perilaku."
                                test={tests.disc}
                            />
                            <TestCard
                                title="Kompetensi"
                                description="Tes kompetensi sesuai bidang yang dilamar."
                                test={tests.competency}
                            />
                            <TestCard
                                title="IST"
                                description="Tes potensi kecerdasan (IQ)."
                                test={tests.ist}
                            />
                        </div>
                    )}
                </div>
            </div>
        </CandidateLayout>
    );
}
