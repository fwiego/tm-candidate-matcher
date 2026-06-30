import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ technologies }) {
    const destroy = (technology) => {
        if (
            confirm(
                `Удалить технологию "${technology.name}"? Это действие необратимо.`,
            )
        ) {
            router.delete(route('admin.technologies.destroy', technology.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Технологии
                    </h2>
                    <Link href={route('admin.technologies.create')}>
                        <PrimaryButton>Добавить технологию</PrimaryButton>
                    </Link>
                </div>
            }
        >
            <Head title="Технологии" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Название
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Группа
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Синонимы
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Используется
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Действия
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {technologies.data.map((technology) => (
                                    <tr key={technology.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {technology.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {technology.group ?? (
                                                <span className="text-gray-300">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {technology.synonyms?.length >
                                            0 ? (
                                                <div className="flex flex-wrap gap-1">
                                                    {technology.synonyms.map(
                                                        (synonym) => (
                                                            <span
                                                                key={synonym}
                                                                className="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700"
                                                            >
                                                                {synonym}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-gray-300">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {technology.requirements_count >
                                                0 && (
                                                <span className="mr-2">
                                                    {
                                                        technology.requirements_count
                                                    }{' '}
                                                    треб.
                                                </span>
                                            )}
                                            {technology.candidates_count >
                                                0 && (
                                                <span>
                                                    {
                                                        technology.candidates_count
                                                    }{' '}
                                                    канд.
                                                </span>
                                            )}
                                            {technology.requirements_count ===
                                                0 &&
                                                technology.candidates_count ===
                                                    0 && (
                                                    <span className="text-gray-300">
                                                        не используется
                                                    </span>
                                                )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                href={route(
                                                    'admin.technologies.edit',
                                                    technology.id,
                                                )}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Редактировать
                                            </Link>
                                            <button
                                                onClick={() =>
                                                    destroy(technology)
                                                }
                                                className="ml-4 text-red-600 hover:text-red-900"
                                            >
                                                Удалить
                                            </button>
                                        </td>
                                    </tr>
                                ))}

                                {technologies.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Технологии не найдены.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {technologies.links && technologies.links.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {technologies.links.map((link, index) => (
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