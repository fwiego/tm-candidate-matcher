import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

const GRADE_LABELS = {
    junior: 'Junior',
    middle: 'Middle',
    senior: 'Senior',
    lead: 'Lead',
};

export default function RequestForm({
    data,
    setData,
    errors,
    processing,
    technologies,
    grades,
    statuses,
    submit,
    submitLabel,
    isEdit = false,
}) {
    const selectedTechIds = data.requirements.map((r) => r.technology_id);

    const addRequirement = () => {
        const firstAvailable = technologies.find(
            (t) => !selectedTechIds.includes(t.id),
        );

        if (!firstAvailable) {
            return;
        }

        setData('requirements', [
            ...data.requirements,
            { technology_id: firstAvailable.id, type: 'must', weight: 5 },
        ]);
    };

    const updateRequirement = (index, field, value) => {
        const next = [...data.requirements];
        next[index] = { ...next[index], [field]: value };
        setData('requirements', next);
    };

    const removeRequirement = (index) => {
        setData(
            'requirements',
            data.requirements.filter((_, i) => i !== index),
        );
    };

    // For each requirement row, technologies already chosen in OTHER rows are hidden.
    const availableTechsFor = (currentTechId) =>
        technologies.filter(
            (t) => t.id === currentTechId || !selectedTechIds.includes(t.id),
        );

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="position" value="Должность" />
                <TextInput
                    id="position"
                    className="mt-1 block w-full"
                    value={data.position}
                    onChange={(e) => setData('position', e.target.value)}
                    required
                    isFocused
                    placeholder="Например: Backend Developer"
                />
                <InputError className="mt-2" message={errors.position} />
            </div>

            <div>
                <InputLabel htmlFor="description" value="Описание" />
                <textarea
                    id="description"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={4}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError className="mt-2" message={errors.description} />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="grade" value="Грейд" />
                    <select
                        id="grade"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        value={data.grade}
                        onChange={(e) => setData('grade', e.target.value)}
                        required
                    >
                        <option value="">Выберите грейд</option>
                        {grades.map((grade) => (
                            <option key={grade} value={grade}>
                                {GRADE_LABELS[grade] ?? grade}
                            </option>
                        ))}
                    </select>
                    <InputError className="mt-2" message={errors.grade} />
                </div>

                <div>
                    <InputLabel htmlFor="needed_by" value="Нужен к дате" />
                    <TextInput
                        id="needed_by"
                        type="date"
                        className="mt-1 block w-full"
                        value={data.needed_by}
                        onChange={(e) =>
                            setData('needed_by', e.target.value)
                        }
                    />
                    <InputError
                        className="mt-2"
                        message={errors.needed_by}
                    />
                </div>

                <div>
                    <InputLabel htmlFor="location" value="Локация" />
                    <TextInput
                        id="location"
                        className="mt-1 block w-full"
                        value={data.location}
                        onChange={(e) =>
                            setData('location', e.target.value)
                        }
                        placeholder="Город / страна"
                    />
                    <InputError
                        className="mt-2"
                        message={errors.location}
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="citizenship"
                        value="Гражданство"
                    />
                    <TextInput
                        id="citizenship"
                        className="mt-1 block w-full"
                        value={data.citizenship}
                        onChange={(e) =>
                            setData('citizenship', e.target.value)
                        }
                    />
                    <InputError
                        className="mt-2"
                        message={errors.citizenship}
                    />
                </div>
            </div>

            <div>
                <InputLabel htmlFor="status" value="Статус" />
                <select
                    id="status"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64"
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value)}
                >
                    {statuses.map((status) => (
                        <option key={status} value={status}>
                            {status === 'draft' && 'Черновик'}
                            {status === 'open' && 'Открыт'}
                            {status === 'closed' && 'Закрыт'}
                        </option>
                    ))}
                </select>
                <p className="mt-1 text-xs text-gray-500">
                    Статусы меняются только вперёд: черновик → открыт →
                    закрыт. Для публикации (открыт) нужно минимум одно
                    обязательное требование.
                </p>
                <InputError className="mt-2" message={errors.status} />
            </div>

            <div>
                <InputLabel value="Требования" />

                <div className="mt-2 space-y-3">
                    {data.requirements.map((req, index) => (
                        <div
                            key={index}
                            className="flex flex-wrap items-center gap-2 rounded-md border border-gray-200 p-3"
                        >
                            <select
                                className="block flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={req.technology_id}
                                onChange={(e) =>
                                    updateRequirement(
                                        index,
                                        'technology_id',
                                        Number(e.target.value),
                                    )
                                }
                            >
                                {availableTechsFor(req.technology_id).map(
                                    (tech) => (
                                        <option
                                            key={tech.id}
                                            value={tech.id}
                                        >
                                            {tech.name}
                                            {tech.group
                                                ? ` (${tech.group})`
                                                : ''}
                                        </option>
                                    ),
                                )}
                            </select>

                            <select
                                className="block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={req.type}
                                onChange={(e) =>
                                    updateRequirement(
                                        index,
                                        'type',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="must">Must</option>
                                <option value="nice">Nice to have</option>
                            </select>

                            <input
                                type="number"
                                min={1}
                                max={10}
                                className="block w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={req.weight}
                                onChange={(e) =>
                                    updateRequirement(
                                        index,
                                        'weight',
                                        Number(e.target.value),
                                    )
                                }
                                title="Вес"
                            />

                            <button
                                type="button"
                                onClick={() => removeRequirement(index)}
                                className="text-sm text-red-600 hover:text-red-900"
                            >
                                Удалить
                            </button>
                        </div>
                    ))}
                </div>

                <button
                    type="button"
                    onClick={addRequirement}
                    disabled={selectedTechIds.length >= technologies.length}
                    className="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-900 disabled:cursor-not-allowed disabled:text-gray-400"
                >
                    + Добавить требование
                </button>

                <InputError
                    className="mt-2"
                    message={errors['requirements']}
                />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>
                    {submitLabel}
                </PrimaryButton>
                <Link href={route('requests.index')}>
                    <SecondaryButton type="button">Отмена</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}