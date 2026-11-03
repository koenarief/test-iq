import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { router, useForm, usePage } from '@inertiajs/react';

export default function MerchantLogoForm({ className = '' }) {
    const { merchantLogoUrl } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        logo: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('merchant.logo.update'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    const remove = () => {
        router.delete(route('merchant.logo.destroy'), { preserveScroll: true });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Logo Merchant
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    Logo ditampilkan di header dashboard merchant. Format PNG,
                    JPG, atau WEBP, maksimal 2 MB.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-4">
                {merchantLogoUrl && (
                    <img
                        src={merchantLogoUrl}
                        alt="Logo merchant"
                        className="h-16 w-auto rounded border border-gray-200 bg-white object-contain p-1"
                    />
                )}

                <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    onChange={(e) => setData('logo', e.target.files[0] ?? null)}
                    className="block w-full text-sm text-gray-700"
                />
                <InputError message={errors.logo} />

                <div className="flex items-center gap-3">
                    <PrimaryButton disabled={processing || !data.logo}>
                        Upload Logo
                    </PrimaryButton>
                    {merchantLogoUrl && (
                        <SecondaryButton type="button" onClick={remove}>
                            Hapus Logo
                        </SecondaryButton>
                    )}
                </div>
            </form>
        </section>
    );
}
