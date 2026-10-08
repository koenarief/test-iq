import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

function buildCategoryWeights(categories, existingWeights = []) {
    return categories.map((category) => {
        const existing = existingWeights.find(
            (weight) => weight.competency_category_id === category.id,
        );

        return {
            competency_category_id: category.id,
            weight: existing ? existing.weight : 0,
        };
    });
}

export default function PositionForm({
    position,
    departments,
    categoriesByDepartment,
    submitUrl,
    method = 'post',
}) {
    const isEdit = Boolean(position);

    const initialDepartment = position?.department ?? departments[0]?.key ?? '';
    const initialCategories = categoriesByDepartment[initialDepartment] ?? [];

    const { data, setData, post, put, processing, errors } = useForm({
        name: position?.name ?? '',
        department: initialDepartment,
        disc_weight: position?.disc_weight ?? 0,
        ist_weight: position?.ist_weight ?? 0,
        is_active: position ? Boolean(position.is_active) : true,
        category_weights: buildCategoryWeights(
            initialCategories,
            position?.category_weights ?? [],
        ),
    });

    useEffect(() => {
        const categories = categoriesByDepartment[data.department] ?? [];
        setData(
            'category_weights',
            buildCategoryWeights(categories, position?.category_weights ?? []),
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.department]);

    const totalWeight = useMemo(() => {
        const categoriesTotal = data.category_weights.reduce(
            (sum, weight) => sum + Number(weight.weight || 0),
            0,
        );

        return (
            Number(data.disc_weight || 0) +
            Number(data.ist_weight || 0) +
            categoriesTotal
        );
    }, [data.disc_weight, data.ist_weight, data.category_weights]);

    const setCategoryWeight = (categoryId, value) => {
        setData(
            'category_weights',
            data.category_weights.map((weight) =>
                weight.competency_category_id === categoryId
                    ? { ...weight, weight: value }
                    : weight,
            ),
        );
    };

    const submit = (e) => {
        e.preventDefault();

        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    const categories = categoriesByDepartment[data.department] ?? [];
    const isBalanced = Math.round(totalWeight * 100) / 100 === 100;

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="name" value="Nama Posisi" />
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
                <InputLabel htmlFor="department" value="Departemen" />
                <select
                    id="department"
                    value={data.department}
                    onChange={(e) => setData('department', e.target.value)}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    {departments.map((department) => (
                        <option key={department.key} value={department.key}>
                            {department.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.department} className="mt-2" />
            </div>

            <div className="rounded-md border border-gray-200 p-4">
                <h3 className="mb-3 text-sm font-semibold text-gray-700">
                    Bobot Job Fit Score (total harus 100%)
                </h3>

                <div className="space-y-3">
                    <div className="flex items-center justify-between gap-4">
                        <InputLabel htmlFor="disc_weight" value="DISC (%)" />
                        <TextInput
                            id="disc_weight"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={data.disc_weight}
                            onChange={(e) =>
                                setData('disc_weight', e.target.value)
                            }
                            className="w-28"
                        />
                    </div>
                    <InputError message={errors.disc_weight} />

                    <div className="flex items-center justify-between gap-4">
                        <InputLabel
                            htmlFor="ist_weight"
                            value="Cognitive/IST (%)"
                        />
                        <TextInput
                            id="ist_weight"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={data.ist_weight}
                            onChange={(e) =>
                                setData('ist_weight', e.target.value)
                            }
                            className="w-28"
                        />
                    </div>
                    <InputError message={errors.ist_weight} />

                    {categories.map((category) => {
                        const weightEntry = data.category_weights.find(
                            (weight) =>
                                weight.competency_category_id === category.id,
                        );

                        return (
                            <div
                                key={category.id}
                                className="flex items-center justify-between gap-4"
                            >
                                <InputLabel
                                    htmlFor={`category_weight_${category.id}`}
                                    value={`${category.name} (%)`}
                                />
                                <TextInput
                                    id={`category_weight_${category.id}`}
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={weightEntry?.weight ?? 0}
                                    onChange={(e) =>
                                        setCategoryWeight(
                                            category.id,
                                            e.target.value,
                                        )
                                    }
                                    className="w-28"
                                />
                            </div>
                        );
                    })}

                    <InputError message={errors.category_weights} />

                    <div
                        className={`mt-3 rounded-md px-3 py-2 text-sm font-semibold ${
                            isBalanced
                                ? 'bg-green-50 text-green-700'
                                : 'bg-red-50 text-red-700'
                        }`}
                    >
                        Total bobot: {totalWeight.toFixed(2)}%{' '}
                        {isBalanced ? '(OK)' : '(harus 100%)'}
                    </div>
                </div>
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                <Link href={route('admin.positions.index')}>
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}
