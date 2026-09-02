import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';

export default function UserForm({
    user,
    merchants = [],
    submitUrl,
    method = 'post',
}) {
    const isEdit = Boolean(user);

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        verified: user ? Boolean(user.email_verified_at) : true,
        role: user?.role ?? 'admin',
        merchant_id: user?.merchant_id ?? '',
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
                <InputLabel htmlFor="name" value="Nama" />
                <TextInput
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.email} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor="password"
                    value={
                        isEdit
                            ? 'Password Baru (kosongkan bila tidak diubah)'
                            : 'Password'
                    }
                />
                <TextInput
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    className="mt-1 block w-full"
                    autoComplete="new-password"
                    required={!isEdit}
                />
                <InputError message={errors.password} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor="password_confirmation"
                    value="Konfirmasi Password"
                />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) =>
                        setData('password_confirmation', e.target.value)
                    }
                    className="mt-1 block w-full"
                    autoComplete="new-password"
                    required={!isEdit}
                />
                <InputError
                    message={errors.password_confirmation}
                    className="mt-2"
                />
            </div>

            <div>
                <InputLabel htmlFor="role" value="Role" />
                <select
                    id="role"
                    value={data.role}
                    onChange={(e) => setData('role', e.target.value)}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="admin">Admin</option>
                    <option value="merchant">Merchant</option>
                </select>
                <InputError message={errors.role} className="mt-2" />
            </div>

            {data.role === 'merchant' && (
                <div>
                    <InputLabel htmlFor="merchant_id" value="Merchant" />
                    <select
                        id="merchant_id"
                        value={data.merchant_id}
                        onChange={(e) =>
                            setData('merchant_id', e.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Pilih merchant...</option>
                        {merchants.map((merchant) => (
                            <option key={merchant.id} value={merchant.id}>
                                {merchant.name}
                            </option>
                        ))}
                    </select>
                    <InputError
                        message={errors.merchant_id}
                        className="mt-2"
                    />
                </div>
            )}

            <div className="flex items-center gap-2">
                <Checkbox
                    id="verified"
                    checked={data.verified}
                    onChange={(e) => setData('verified', e.target.checked)}
                />
                <InputLabel htmlFor="verified" value="Email terverifikasi" />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                <Link href={route('admin.users.index')}>
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}
