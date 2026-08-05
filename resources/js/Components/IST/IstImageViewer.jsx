import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { Expand, ImageOff, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function IstImageViewer({ image, className = '' }) {
    const url = image?.url ?? null;
    const alt = image?.alt?.trim() || 'Ilustrasi soal IST';
    const [isOpen, setIsOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(Boolean(url));
    const [hasError, setHasError] = useState(false);

    useEffect(() => {
        setIsOpen(false);
        setIsLoading(Boolean(url));
        setHasError(false);
    }, [url]);

    if (!url) {
        return null;
    }

    const handleError = () => {
        setIsLoading(false);
        setHasError(true);
        setIsOpen(false);
    };

    return (
        <>
            <figure className={`rounded-xl border border-zinc-800 bg-zinc-950/70 p-3 ${className}`}>
                <div className="relative flex min-h-40 items-center justify-center overflow-hidden rounded-lg bg-zinc-950">
                    {isLoading && (
                        <div className="absolute inset-0 flex items-center justify-center text-xs text-zinc-500" role="status">
                            Memuat gambar…
                        </div>
                    )}

                    {hasError ? (
                        <div className="flex min-h-40 flex-col items-center justify-center gap-2 px-4 text-center text-sm text-zinc-500" role="alert">
                            <ImageOff className="h-7 w-7 text-zinc-600" aria-hidden="true" />
                            Gambar tidak dapat dimuat.
                        </div>
                    ) : (
                        <img
                            src={url}
                            alt={alt}
                            loading="lazy"
                            onLoad={() => setIsLoading(false)}
                            onError={handleError}
                            className={`max-h-[28rem] w-full object-contain ${isLoading ? 'opacity-0' : 'opacity-100'}`}
                        />
                    )}
                </div>

                {!hasError && (
                    <div className="mt-3 flex justify-end">
                        <button
                            type="button"
                            onClick={(event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                setIsOpen(true);
                            }}
                            className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-900 px-3 text-xs font-semibold text-zinc-200 hover:border-blue-500/50 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                            aria-label={`Perbesar gambar: ${alt}`}
                        >
                            <Expand className="h-4 w-4 text-blue-400" aria-hidden="true" />
                            Perbesar
                        </button>
                    </div>
                )}
            </figure>

            <Dialog open={isOpen} onClose={setIsOpen} className="relative z-50">
                <div className="fixed inset-0 bg-zinc-950/90 backdrop-blur-sm" aria-hidden="true" />
                <div className="fixed inset-0 overflow-y-auto p-4 sm:p-8">
                    <div className="flex min-h-full items-center justify-center">
                        <DialogPanel className="w-full max-w-6xl rounded-2xl border border-zinc-700 bg-zinc-900 p-4 shadow-2xl sm:p-6">
                            <div className="mb-4 flex items-center justify-between gap-4">
                                <DialogTitle className="text-sm font-semibold text-white">
                                    Tampilan gambar diperbesar
                                </DialogTitle>
                                <button
                                    type="button"
                                    onClick={() => setIsOpen(false)}
                                    className="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-zinc-700 text-zinc-300 hover:bg-zinc-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                                    aria-label="Tutup tampilan gambar"
                                >
                                    <X className="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                            <div className="flex max-h-[78vh] min-h-64 items-center justify-center overflow-auto rounded-xl bg-zinc-950 p-3">
                                <img src={url} alt={alt} className="max-h-[74vh] max-w-full object-contain" />
                            </div>
                        </DialogPanel>
                    </div>
                </div>
            </Dialog>
        </>
    );
}
