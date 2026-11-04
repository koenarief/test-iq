import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';

export default function MerchantForm({ merchant, submitUrl, method = 'post' }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: merchant?.name ?? '',
        is_active: merchant ? Boolean(merchant.is_active) : true,
        is_trial: merchant ? Boolean(merchant.is_trial) : false,
        max_participants: merchant?.max_participants ?? '',
    });

    const submit = (e) => {
        e.preventDefault();

        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="name" value="Nama Merchant" />
                <TextInput
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onChange={(e) => setData('is_active', e.target.checked)}
                />
                <InputLabel htmlFor="is_active" value="Aktif" />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_trial"
                    checked={data.is_trial}
                    onChange={(e) => setData('is_trial', e.target.checked)}
                />
                <InputLabel htmlFor="is_trial" value="Status Trial" />
            </div>

            {data.is_trial && (
                <div>
                    <InputLabel htmlFor="max_participants" value="Maksimal Peserta" />
                    <TextInput
                        id="max_participants"
                        type="number"
                        min="1"
                        value={data.max_participants}
                        onChange={(e) => setData('max_participants', e.target.value)}
                        className="mt-1 block w-full"
                    />
                    {merchant?.participant_count !== undefined && (
                        <p className="mt-1 text-xs text-gray-500">
                            Peserta saat ini: {merchant.participant_count}
                        </p>
                    )}
                    <InputError message={errors.max_participants} className="mt-2" />
                </div>
            )}

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                <Link href={route('admin.merchants.index')}>
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}
