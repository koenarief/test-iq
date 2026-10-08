import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import PositionForm from './Partials/Form';

export default function Edit({ position, departments, categoriesByDepartment }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Ubah Posisi — {position.name}
                </h2>
            }
        >
            <Head title="Ubah Posisi" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <PositionForm
                            position={position}
                            departments={departments}
                            categoriesByDepartment={categoriesByDepartment}
                            submitUrl={route(
                                'admin.positions.update',
                                position.id,
                            )}
                            method="put"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
