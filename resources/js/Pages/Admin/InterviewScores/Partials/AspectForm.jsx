import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';

/**
 * Shared 1-100 weighted-aspect form for both Interview HRD and Interview
 * User — same shape, different aspect lists/weights/routes (konsep-hrd.pdf
 * §5-6: both forms score aspects 1-100, only the aspect set/weights and the
 * combined-score field differ).
 */
export default function AspectForm({
    title,
    aspects,
    notesKey,
    candidateAssessment,
    weights,
    updateRoute,
    cancelRoute,
}) {
    const initialData = {};
    aspects.forEach((aspect) => {
        initialData[aspect.key] = candidateAssessment[aspect.key] ?? '';
    });
    initialData[notesKey] = candidateAssessment[notesKey] ?? '';

    const { data, setData, put, processing, errors } = useForm(initialData);

    const submit = (e) => {
        e.preventDefault();
        put(updateRoute);
    };

    return (
        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
            <h3 className="mb-4 text-lg font-semibold text-gray-900">
                {title} — {candidateAssessment.candidate.name}
            </h3>

            <form onSubmit={submit} className="space-y-6">
                {aspects.map((aspect) => (
                    <div key={aspect.key}>
                        <InputLabel
                            htmlFor={aspect.key}
                            value={`${aspect.label} (bobot ${Math.round(weights[aspect.weightKey] * 100)}%)`}
                        />
                        <TextInput
                            id={aspect.key}
                            type="number"
                            min="1"
                            max="100"
                            step="0.01"
                            value={data[aspect.key]}
                            onChange={(e) =>
                                setData(aspect.key, e.target.value)
                            }
                            className="mt-1 block w-full"
                            required
                        />
                        <InputError
                            message={errors[aspect.key]}
                            className="mt-2"
                        />
                    </div>
                ))}

                <div>
                    <InputLabel
                        htmlFor={notesKey}
                        value="Catatan / bukti (opsional)"
                    />
                    <textarea
                        id={notesKey}
                        value={data[notesKey]}
                        onChange={(e) => setData(notesKey, e.target.value)}
                        rows={4}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError message={errors[notesKey]} className="mt-2" />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>
                        Simpan
                    </PrimaryButton>
                    <Link href={cancelRoute}>
                        <SecondaryButton type="button">Batal</SecondaryButton>
                    </Link>
                </div>
            </form>
        </div>
    );
}
