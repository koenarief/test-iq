import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Calendar, Clock3, ShieldCheck, User, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import IstStateNotice from '@/Components/IST/IstStateNotice';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Biodata({ merchantName = null }) {
    const [availabilityError, setAvailabilityError] = useState(null);
    const { data, setData, post, processing, errors, clearErrors } = useForm({
        participant_name: '',
        age: '',
        gender: '',
    });

    useEffect(() => {
        const showSafeAvailabilityError = () => {
            setAvailabilityError('Asesmen belum dapat dimulai saat ini. Silakan coba kembali setelah layanan tersedia.');

            return false;
        };
        const removeInvalidListener = router.on('invalid', showSafeAvailabilityError);
        const removeExceptionListener = router.on('exception', showSafeAvailabilityError);

        return () => {
            removeInvalidListener();
            removeExceptionListener();
        };
    }, []);

    const handleSubmit = (event) => {
        event.preventDefault();

        if (processing) {
            return;
        }

        setAvailabilityError(null);
        clearErrors();

        post(route('ist.start'), {
            preserveScroll: true,
            onError: (responseErrors) => {
                if (responseErrors?.request) {
                    setAvailabilityError('Asesmen belum dapat dimulai saat ini. Silakan coba kembali setelah layanan tersedia.');
                }
            },
        });
    };

    const inputClass = (hasError) => `min-h-12 w-full rounded-xl border bg-zinc-950/90 px-4 text-sm text-white placeholder:text-zinc-600 focus:outline-none focus:ring-2 ${
        hasError
            ? 'border-red-500/70 focus:border-red-500 focus:ring-red-500/20'
            : 'border-zinc-700 focus:border-blue-500 focus:ring-blue-500/20'
    }`;

    return (
        <PublicLayout>
            <Head title="Tes Kemampuan Kognitif Adaptasi" />

            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-4 py-8 sm:px-6 sm:py-12">
                <div className="mx-auto w-full max-w-3xl">
                    <Link
                        href={route('landing')}
                        className="mb-6 inline-flex min-h-11 items-center gap-2 rounded-lg text-xs font-mono text-zinc-400 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Kembali ke Pilihan Tes
                    </Link>

                    <div className="grid overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/90 shadow-2xl lg:grid-cols-[0.8fr_1.2fr]">
                        <section className="border-b border-zinc-800 bg-gradient-to-br from-blue-950/60 via-indigo-950/30 to-zinc-900 p-6 lg:border-b-0 lg:border-r lg:p-8">
                            <span className="text-xs font-mono uppercase tracking-widest text-blue-300">Tes Kemampuan Kognitif Adaptasi</span>
                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Isi Data Peserta</h1>
                            <p className="mt-3 text-sm leading-relaxed text-zinc-300">
                                Lengkapi data berikut sebelum memulai asesmen.
                            </p>

                            <ul className="mt-7 space-y-4 text-sm text-zinc-300">
                                <li className="flex items-start gap-3">
                                    <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-300">
                                        <span className="font-mono text-xs font-bold">9</span>
                                    </span>
                                    <span>Asesmen terdiri dari sembilan subtes dengan waktu pengerjaan inti sekitar 45 menit.</span>
                                </li>
                                <li className="flex items-start gap-3">
                                    <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-300">
                                        <Clock3 className="h-4 w-4" aria-hidden="true" />
                                    </span>
                                    <span>Waktu pengerjaan inti sekitar 45 menit. Waktu membaca petunjuk tidak dihitung.</span>
                                </li>
                                <li className="flex items-start gap-3">
                                    <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-300">
                                        <ShieldCheck className="h-4 w-4" aria-hidden="true" />
                                    </span>
                                    <span>Subtes yang telah diselesaikan akan dikunci dan tidak dapat dibuka kembali.</span>
                                </li>
                            </ul>
                        </section>

                        <section className="p-6 sm:p-8">
                            <div className="mb-6">
                                <p className="text-xs font-mono uppercase tracking-widest text-blue-400">Biodata peserta</p>
                                <h2 className="mt-1 text-xl font-bold text-white">Lengkapi data diri dengan benar</h2>
                                {merchantName && (
                                    <span className="mt-3 inline-flex items-center gap-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-300">
                                        Terdaftar melalui merchant: {merchantName}
                                    </span>
                                )}
                            </div>

                            {availabilityError && (
                                <div className="mb-5">
                                    <IstStateNotice tone="error" title="Asesmen belum tersedia">
                                        {availabilityError}
                                    </IstStateNotice>
                                </div>
                            )}

                            {errors.request && !availabilityError && (
                                <div className="mb-5">
                                    <IstStateNotice tone="error" title="Data belum dapat diproses">
                                        Permintaan tidak dapat diproses. Periksa kembali data yang dimasukkan.
                                    </IstStateNotice>
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                                <div>
                                    <label htmlFor="participant_name" className="mb-2 block text-sm font-semibold text-zinc-200">
                                        Nama lengkap <span className="text-red-400" aria-hidden="true">*</span>
                                    </label>
                                    <div className="relative">
                                        <User className="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-zinc-500" aria-hidden="true" />
                                        <input
                                            id="participant_name"
                                            type="text"
                                            autoComplete="name"
                                            value={data.participant_name}
                                            onChange={(event) => setData('participant_name', event.target.value)}
                                            aria-invalid={Boolean(errors.participant_name)}
                                            aria-describedby={errors.participant_name ? 'participant_name-error' : undefined}
                                            className={`${inputClass(Boolean(errors.participant_name))} pl-12`}
                                            placeholder="Masukkan nama lengkap"
                                        />
                                    </div>
                                    {errors.participant_name && (
                                        <p id="participant_name-error" className="mt-2 text-xs text-red-400" role="alert">
                                            {errors.participant_name}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label htmlFor="age" className="mb-2 block text-sm font-semibold text-zinc-200">
                                            Usia <span className="text-red-400" aria-hidden="true">*</span>
                                        </label>
                                        <div className="relative">
                                            <Calendar className="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-zinc-500" aria-hidden="true" />
                                            <input
                                                id="age"
                                                type="number"
                                                min="10"
                                                max="100"
                                                inputMode="numeric"
                                                value={data.age}
                                                onChange={(event) => setData('age', event.target.value)}
                                                aria-invalid={Boolean(errors.age)}
                                                aria-describedby={errors.age ? 'age-error' : undefined}
                                                className={`${inputClass(Boolean(errors.age))} pl-12`}
                                                placeholder="10–100"
                                            />
                                        </div>
                                        {errors.age && <p id="age-error" className="mt-2 text-xs text-red-400" role="alert">{errors.age}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="gender" className="mb-2 block text-sm font-semibold text-zinc-200">
                                            Jenis kelamin <span className="text-red-400" aria-hidden="true">*</span>
                                        </label>
                                        <div className="relative">
                                            <Users className="pointer-events-none absolute left-4 top-3.5 z-10 h-5 w-5 text-zinc-500" aria-hidden="true" />
                                            <select
                                                id="gender"
                                                value={data.gender}
                                                onChange={(event) => setData('gender', event.target.value)}
                                                aria-invalid={Boolean(errors.gender)}
                                                aria-describedby={errors.gender ? 'gender-error' : undefined}
                                                className={`${inputClass(Boolean(errors.gender))} appearance-none pl-12`}
                                            >
                                                <option value="">Pilih</option>
                                                <option value="L">Laki-laki</option>
                                                <option value="P">Perempuan</option>
                                            </select>
                                        </div>
                                        {errors.gender && <p id="gender-error" className="mt-2 text-xs text-red-400" role="alert">{errors.gender}</p>}
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 hover:from-blue-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900"
                                >
                                    {processing ? 'Menyiapkan sesi…' : 'Lanjut ke Petunjuk Asesmen'}
                                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
