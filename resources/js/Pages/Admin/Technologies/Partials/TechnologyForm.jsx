import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function TechnologyForm({
    data,
    setData,
    errors,
    processing,
    groups,
    submit,
    submitLabel,
}) {
    const addSynonym = () => {
        setData('synonyms', [...data.synonyms, '']);
    };

    const updateSynonym = (index, value) => {
        const next = [...data.synonyms];
        next[index] = value;
        setData('synonyms', next);
    };

    const removeSynonym = (index) => {
        setData(
            'synonyms',
            data.synonyms.filter((_, i) => i !== index),
        );
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="name" value="Название" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    isFocused
                    placeholder="Например: PHP"
                />
                <InputError className="mt-2" message={errors.name} />
            </div>

            <div>
                <InputLabel htmlFor="group" value="Группа" />
                <TextInput
                    id="group"
                    className="mt-1 block w-full"
                    value={data.group}
                    onChange={(e) => setData('group', e.target.value)}
                    list="technology-groups"
                    placeholder="Например: Backend"
                />
                <datalist id="technology-groups">
                    {groups.map((group) => (
                        <option key={group} value={group} />
                    ))}
                </datalist>
                <InputError className="mt-2" message={errors.group} />
            </div>

            <div>
                <InputLabel value="Синонимы" />
                <p className="mt-1 text-xs text-gray-500">
                    Альтернативные названия, по которым технология будет
                    распознаваться в резюме (например, для "JavaScript":
                    "JS", "ECMAScript").
                </p>

                <div className="mt-2 space-y-2">
                    {data.synonyms.map((synonym, index) => (
                        <div key={index} className="flex items-center gap-2">
                            <TextInput
                                className="block w-full"
                                value={synonym}
                                onChange={(e) =>
                                    updateSynonym(index, e.target.value)
                                }
                                placeholder="Синоним"
                            />
                            <button
                                type="button"
                                onClick={() => removeSynonym(index)}
                                className="text-sm text-red-600 hover:text-red-900"
                            >
                                Удалить
                            </button>
                        </div>
                    ))}
                </div>

                <button
                    type="button"
                    onClick={addSynonym}
                    className="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-900"
                >
                    + Добавить синоним
                </button>

                <InputError className="mt-2" message={errors['synonyms']} />
                <InputError className="mt-2" message={errors['synonyms.0']} />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>
                    {submitLabel}
                </PrimaryButton>
                <Link href={route('admin.technologies.index')}>
                    <SecondaryButton type="button">Отмена</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}