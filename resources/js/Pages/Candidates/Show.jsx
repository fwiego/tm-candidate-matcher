import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

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

function CoverageBar({ percent }) {
    const color =
        percent >= 80
            ? 'bg-green-500'
            : percent >= 50
              ? 'bg-yellow-400'
              : 'bg-red-400';

    return (
        <div className="flex items-center gap-2">
            <div className="h-2 w-24 overflow-hidden rounded-full bg-gray-200">
                <div
                    className={`h-full rounded-full ${color}`}
                    style={{ width: `${percent}%` }}
                />
            </div>
            <span className="text-xs font-medium text-gray-700">
                {percent}%
            </span>
        </div>
    );
}

export default function Show({ candidate, assessments }) {
    const { auth } = usePage().props;
    const roles = auth.roles ?? [];
    const canEdit = roles.includes('admin') || roles.includes('manager');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {candidate.full_name}
                    </h2>
                    <div className="flex gap-2">
                        {canEdit && (
                            <Link href={route('candidates.edit', candidate.id)}>
                                <PrimaryButton>Редактировать</PrimaryButton>
                            </Link>
                        )}
                        <Link
                            href={route('assessments.create', {
                                candidate_id: candidate.id,
                            })}
                        >
                            <SecondaryButton>Сверить с запросом</SecondaryButton>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={candidate.full_name} />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <dl className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="font-medium text-gray-500">Грейд</dt>
                                <dd className="text-gray-900">
                                    {candidate.grade ? GRADE_LABELS[candidate.grade] : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">Локация</dt>
                                <dd className="text-gray-900">{candidate.location || '—'}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">Загрузил</dt>
                                <dd className="text-gray-900">{candidate.uploader?.name}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">Файл резюме</dt>
                                <dd className="text-gray-900">
                                    {candidate.file_path?.split('/').pop()}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-sm font-semibold text-gray-700">
                            Распознанные навыки
                        </h3>

                        {candidate.skills?.length > 0 ? (
                            <div className="flex flex-wrap gap-2">
                                {candidate.skills.map((skill) => (
                                    <span
                                        key={skill.id}
                                        className="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800"
                                    >
                                        {skill.name}
                                        {skill.group ? ` (${skill.group})` : ''}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400">
                                Технологии не распознаны.
                            </p>
                        )}
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-gray-700">
                                История сверок
                            </h3>
                            <Link
                                href={route('assessments.create', {
                                    candidate_id: candidate.id,
                                })}
                                className="text-xs font-medium text-indigo-600 hover:text-indigo-900"
                            >
                                + Новая сверка
                            </Link>
                        </div>

                        {assessments?.length > 0 ? (
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr className="text-left text-xs font-medium uppercase text-gray-400">
                                        <th className="pb-2 pr-4">Вакансия</th>
                                        <th className="pb-2 pr-4">Статус</th>
                                        <th className="pb-2 pr-4">Грейд</th>
                                        <th className="pb-2 pr-4">Покрытие</th>
                                        <th className="pb-2 pr-4">Дата</th>
                                        <th className="pb-2"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {assessments.map((a) => (
                                        <tr key={a.id} className="text-sm">
                                            <td className="py-2 pr-4 font-medium text-gray-900">
                                                <Link
                                                    href={route('requests.show', a.request.id)}
                                                    className="hover:text-indigo-600"
                                                >
                                                    {a.request.position}
                                                </Link>
                                            </td>
                                            <td className="py-2 pr-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_COLORS[a.request.status]}`}
                                                >
                                                    {STATUS_LABELS[a.request.status]}
                                                </span>
                                            </td>
                                            <td className="py-2 pr-4 text-gray-500">
                                                {a.request.grade
                                                    ? GRADE_LABELS[a.request.grade]
                                                    : '—'}
                                            </td>
                                            <td className="py-2 pr-4">
                                                <CoverageBar percent={a.coverage_percent} />
                                            </td>
                                            <td className="py-2 pr-4 text-xs text-gray-400">
                                                {a.updated_at}
                                            </td>
                                            <td className="py-2">
                                                <Link
                                                    href={route('assessments.show', a.id)}
                                                    className="text-xs text-indigo-600 hover:underline"
                                                >
                                                    Детали
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="text-sm text-gray-400">
                                Сверок пока нет. Нажмите «Сверить с запросом», чтобы начать.
                            </p>
                        )}
                    </div>

                    {candidate.raw_text && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-sm font-semibold text-gray-700">
                                Распознанный текст резюме
                            </h3>
                            <pre className="max-h-96 overflow-y-auto whitespace-pre-wrap text-xs text-gray-600">
                                {candidate.raw_text}
                            </pre>
                        </div>
                    )}

                    <Link
                        href={route('candidates.index')}
                        className="text-sm text-indigo-600 hover:text-indigo-900"
                    >
                        ← Назад к списку
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}