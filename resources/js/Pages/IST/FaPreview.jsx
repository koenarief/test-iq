import React from 'react';

const OptionCard = ({ label, children }) => {
    return (
        <div className="flex flex-col items-center gap-2">
            <div className="flex h-28 w-32 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-sm">
                {children}
            </div>

            <div className="text-sm font-semibold text-slate-700">
                {label}
            </div>
        </div>
    );
};

export default function FaPreview() {
    return (
        <div className="min-h-screen bg-slate-50 px-6 py-10">
            <div className="mx-auto max-w-5xl">

                <div className="mb-8">
                    <div className="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
                        Preview FA
                    </div>

                    <h1 className="text-2xl font-bold text-slate-900">
                        Pilih bentuk yang dapat dibuat dari potongan berikut
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-slate-600">
                        Bayangkan potongan dapat diputar. Gunakan seluruh potongan
                        tanpa tumpang tindih dan tanpa menyisakan ruang kosong.
                    </p>
                </div>

                {/* PILIHAN TARGET */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="mb-5 text-sm font-semibold text-slate-700">
                        Pilihan bentuk
                    </div>

                    <div className="grid grid-cols-2 gap-6 md:grid-cols-5">

                        {/* A - Persegi */}
                        <OptionCard label="A">
                            <svg
                                viewBox="0 0 120 90"
                                className="h-20 w-24"
                                aria-label="Pilihan A"
                            >
                                <rect
                                    x="32"
                                    y="17"
                                    width="56"
                                    height="56"
                                    fill="currentColor"
                                />
                            </svg>
                        </OptionCard>

                        {/* B - Persegi panjang */}
                        <OptionCard label="B">
                            <svg
                                viewBox="0 0 120 90"
                                className="h-20 w-24"
                                aria-label="Pilihan B"
                            >
                                <rect
                                    x="15"
                                    y="27"
                                    width="90"
                                    height="36"
                                    fill="currentColor"
                                />
                            </svg>
                        </OptionCard>

                        {/* C - Segitiga */}
                        <OptionCard label="C">
                            <svg
                                viewBox="0 0 120 90"
                                className="h-20 w-24"
                                aria-label="Pilihan C"
                            >
                                <polygon
                                    points="60,12 105,72 15,72"
                                    fill="currentColor"
                                />
                            </svg>
                        </OptionCard>

                        {/* D - Trapesium */}
                        <OptionCard label="D">
                            <svg
                                viewBox="0 0 120 90"
                                className="h-20 w-24"
                                aria-label="Pilihan D"
                            >
                                <polygon
                                    points="35,20 85,20 105,70 15,70"
                                    fill="currentColor"
                                />
                            </svg>
                        </OptionCard>

                        {/* E - Jajargenjang */}
                        <OptionCard label="E">
                            <svg
                                viewBox="0 0 120 90"
                                className="h-20 w-24"
                                aria-label="Pilihan E"
                            >
                                <polygon
                                    points="35,20 105,20 85,70 15,70"
                                    fill="currentColor"
                                />
                            </svg>
                        </OptionCard>
                    </div>
                </div>

                {/* SOAL */}
                <div className="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="mb-2 text-sm font-semibold text-slate-500">
                        Contoh Soal
                    </div>

                    <div className="mb-6 text-lg font-semibold text-slate-900">
                        Jika kedua potongan berikut disusun, bentuk manakah yang dapat dibuat?
                    </div>

                    <div className="flex min-h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50">

                        <svg
                            viewBox="0 0 420 220"
                            className="h-56 w-full max-w-xl"
                            aria-label="Potongan soal FA"
                        >
                            {/* POTONGAN 1 */}
                            <polygon
                                points="55,55 175,55 175,145 110,145"
                                fill="currentColor"
                            />

                            {/* POTONGAN 2 - sengaja diputar */}
                            <polygon
                                points="275,60 350,95 310,175 235,140"
                                fill="currentColor"
                            />
                        </svg>
                    </div>

                    <div className="mt-6 grid grid-cols-5 gap-3">
                        {['A', 'B', 'C', 'D', 'E'].map((answer) => (
                            <button
                                key={answer}
                                type="button"
                                className="rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:border-slate-900 hover:bg-slate-50"
                            >
                                {answer}
                            </button>
                        ))}
                    </div>

                    <div className="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Preview sementara — belum terhubung ke scoring atau database.
                    </div>
                </div>
            </div>
        </div>
    );
}