import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import UserForm from './Partials/Form';

export default function Create() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tambah User
                </h2>
            }
        >
            <Head title="Tambah User" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <UserForm
                            submitUrl={route('admin.users.store')}
                            method="post"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
