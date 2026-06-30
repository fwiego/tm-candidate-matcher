import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ candidate, technologies }) {
    const { data, setData, put, errors, processing } = useForm({
        full_name: candidate.full_name,
        grade: candidate.grade ?? '',
        location: candidate.location ?? '',
        skills: candidate.skill_ids,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('candidates.update', candidate.id));
    };

    const toggleSkill = (techId) => {
        if (data.skills.includes(techId)) {
            setData(
                'skills',
                data.skills.filter((id) => id !== techId),
            );
        } else {
            setData('skills', [...data.skills, techId]);
        }
    };

    // Group technologies by their group for a more readable checklist.
    const grouped = technologies.reduce((acc, tech) => {
        const key = tech.group ?? 'Прочее';
        acc[key] = acc[key] ?? [];
        acc[key].push(tech);
        return acc;
    }, {});

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Редактирование кандидата
                </h2>
            }
        >
            <Head title="Редактирование кандидата" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel
                                    htmlFor="full_name"
                                    value="ФИО"
                                />
                                <TextInput
                                    id="full_name"
                                    className="mt-1 block w-full"
                                    value={data.full_name}
                                    onChange={(e) =>
                                        setData(
                                            'full_name',
                                            e.target.value,
                                        )
                                    }
                                    required
                                    isFocused
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.full_name}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel
                                        htmlFor="grade"
                                        value="Грейд"
                                    />
                                    <select
                                        id="grade"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.grade}
                                        onChange={(e) =>
                                            setData('grade', e.target.value)
                                        }
                                    >
                                        <option value="">Не указан</option>
                                        <option value="junior">Junior</option>
                                        <option value="middle">Middle</option>
                                        <option value="senior">Senior</option>
                                        <option value="lead">Lead</option>
                                    </select>
                                    <InputError
                                        className="mt-2"
                                        message={errors.grade}
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor="location"
                                        value="Локация"
                                    />
                                    <TextInput
                                        id="location"
                                        className="mt-1 block w-full"
                                        value={data.location}
                                        onChange={(e) =>
                                            setData(
                                                'location',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.location}
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Навыки" />
                                <p className="mt-1 text-xs text-gray-500">
                                    Технологии, распознанные автоматически из
                                    резюме. Можно скорректировать вручную.
                                </p>

                                <div className="mt-3 space-y-4">
                                    {Object.entries(grouped).map(
                                        ([group, techs]) => (
                                            <div key={group}>
                                                <h4 className="mb-1 text-xs font-semibold uppercase text-gray-400">
                                                    {group}
                                                </h4>
                                                <div className="flex flex-wrap gap-2">
                                                    {techs.map((tech) => {
                                                        const checked =
                                                            data.skills.includes(
                                                                tech.id,
                                                            );
                                                        return (
                                                            <label
                                                                key={tech.id}
                                                                className={`cursor-pointer rounded-full border px-3 py-1 text-xs font-medium ${
                                                                    checked
                                                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                                                        : 'border-gray-300 text-gray-600'
                                                                }`}
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    className="sr-only"
                                                                    checked={
                                                                        checked
                                                                    }
                                                                    onChange={() =>
                                                                        toggleSkill(
                                                                            tech.id,
                                                                        )
                                                                    }
                                                                />
                                                                {tech.name}
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                                <InputError
                                    className="mt-2"
                                    message={errors.skills}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    Сохранить
                                </PrimaryButton>
                                <Link
                                    href={route(
                                        'candidates.show',
                                        candidate.id,
                                    )}
                                >
                                    <SecondaryButton type="button">
                                        Отмена
                                    </SecondaryButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}