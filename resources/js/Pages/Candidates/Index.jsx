import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const GRADE_LABELS = {
    junior: 'Junior',
    middle: 'Middle',
    senior: 'Senior',
    lead: 'Lead',
};

export default function Index({ candidates, filters, technologies }) {
    const { auth } = usePage().props;
    const roles = auth.roles ?? [];
    const canUpload = roles.includes('admin') || roles.includes('manager');

    const [localFilters, setLocalFilters] = useState({
        search: filters.search ?? '',
        grade: filters.grade ?? '',
        technology_id: filters.technology_id ?? '',
    });

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('candidates.index'), localFilters, {
            preserveState: true,
        });
    };

    const resetFilters = () => {
        const empty = { search: '', grade: '', technology_id: '' };
        setLocalFilters(empty);
        router.get(route('candidates.index'), empty);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Кандидаты
                    </h2>
                    {canUpload && (
                        <Link href={route('candidates.create')}>
                            <PrimaryButton>Загрузить резюме</PrimaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Кандидаты" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={applyFilters}
                        className="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div>
                            <label className="block text-xs font-medium text-gray-500">
                                Поиск по имени
                            </label>
                            <input
                                type="text"
                                className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={localFilters.search}
                                onChange={(e) =>
                                    setLocalFilters({
                                        ...localFilters,
                                        search: e.target.value,
                                    })
                                }
                                placeholder="Имя кандидата"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-500">
                                Грейд
                            </label>
                            <select
                                className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={localFilters.grade}
                                onChange={(e) =>
                                    setLocalFilters({
                                        ...localFilters,
                                        grade: e.target.value,
                                    })
                                }
                            >
                                <option value="">Все</option>
                                <option value="junior">Junior</option>
                                <option value="middle">Middle</option>
                                <option value="senior">Senior</option>
                                <option value="lead">Lead</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-500">
                                Технология
                            </label>
                            <select
                                className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={localFilters.technology_id}
                                onChange={(e) =>
                                    setLocalFilters({
                                        ...localFilters,
                                        technology_id: e.target.value,
                                    })
                                }
                            >
                                <option value="">Все</option>
                                {technologies.map((tech) => (
                                    <option key={tech.id} value={tech.id}>
                                        {tech.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <button
                            type="submit"
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Применить
                        </button>
                        <button
                            type="button"
                            onClick={resetFilters}
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Сбросить
                        </button>
                    </form>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        ФИО
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Грейд
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Локация
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Навыки
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {candidates.data.map((candidate) => (
                                    <tr
                                        key={candidate.id}
                                        className="cursor-pointer hover:bg-gray-50"
                                        onClick={() =>
                                            router.visit(
                                                route(
                                                    'candidates.show',
                                                    candidate.id,
                                                ),
                                            )
                                        }
                                    >
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {candidate.full_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {candidate.grade
                                                ? GRADE_LABELS[
                                                      candidate.grade
                                                  ]
                                                : '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {candidate.location || '—'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            <div className="flex flex-wrap gap-1">
                                                {candidate.skills
                                                    .slice(0, 5)
                                                    .map((skill) => (
                                                        <span
                                                            key={skill.id}
                                                            className="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-800"
                                                        >
                                                            {skill.name}
                                                        </span>
                                                    ))}
                                                {candidate.skills_count >
                                                    5 && (
                                                    <span className="text-xs text-gray-400">
                                                        +
                                                        {candidate.skills_count -
                                                            5}
                                                    </span>
                                                )}
                                                {candidate.skills_count ===
                                                    0 && (
                                                    <span className="text-xs text-gray-300">
                                                        —
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {candidates.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Кандидаты не найдены.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {candidates.links && candidates.links.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {candidates.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`rounded-md px-3 py-1 text-sm ${
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50'
                                    } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}