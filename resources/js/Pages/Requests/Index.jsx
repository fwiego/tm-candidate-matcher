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

const STATUS_LABELS = {
    draft: 'Черновик',
    open: 'Открыт',
    closed: 'Закрыт',
};

const STATUS_COLORS = {
    draft: 'bg-gray-100 text-gray-700',
    open: 'bg-green-100 text-green-700',
    closed: 'bg-red-100 text-red-700',
};

export default function Index({ requests, filters }) {
    const { auth } = usePage().props;
    const roles = auth.roles ?? [];
    const canCreate =
        roles.includes('admin') || roles.includes('manager');

    const [localFilters, setLocalFilters] = useState({
        status: filters.status ?? '',
        grade: filters.grade ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('requests.index'), localFilters, {
            preserveState: true,
        });
    };

    const resetFilters = () => {
        const empty = {
            status: '',
            grade: '',
            date_from: '',
            date_to: '',
        };
        setLocalFilters(empty);
        router.get(route('requests.index'), empty);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Запросы
                    </h2>
                    {canCreate && (
                        <Link href={route('requests.create')}>
                            <PrimaryButton>Создать запрос</PrimaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Запросы" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={applyFilters}
                        className="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div>
                            <label className="block text-xs font-medium text-gray-500">
                                Статус
                            </label>
                            <select
                                className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={localFilters.status}
                                onChange={(e) =>
                                    setLocalFilters({
                                        ...localFilters,
                                        status: e.target.value,
                                    })
                                }
                            >
                                <option value="">Все</option>
                                <option value="draft">Черновик</option>
                                <option value="open">Открыт</option>
                                <option value="closed">Закрыт</option>
                            </select>
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
                                Дата создания от
                            </label>
                            <input
                                type="date"
                                className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={localFilters.date_from}
                                onChange={(e) =>
                                    setLocalFilters({
                                        ...localFilters,
                                        date_from: e.target.value,
                                    })
                                }
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-500">
                                до
                            </label>
                            <input
                                type="date"
                                className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={localFilters.date_to}
                                onChange={(e) =>
                                    setLocalFilters({
                                        ...localFilters,
                                        date_to: e.target.value,
                                    })
                                }
                            />
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
                                        Должность
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Грейд
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Статус
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Требований
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Создал
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Создан
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {requests.data.map((req) => (
                                    <tr
                                        key={req.id}
                                        className="cursor-pointer hover:bg-gray-50"
                                        onClick={() =>
                                            router.visit(
                                                route('requests.show', req.id),
                                            )
                                        }
                                    >
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {req.position}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {GRADE_LABELS[req.grade]}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_COLORS[req.status]}`}
                                            >
                                                {STATUS_LABELS[req.status]}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {req.requirements_count}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {req.creator?.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {new Date(
                                                req.created_at,
                                            ).toLocaleDateString('ru-RU')}
                                        </td>
                                    </tr>
                                ))}

                                {requests.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Запросы не найдены.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {requests.links && requests.links.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {requests.links.map((link, index) => (
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