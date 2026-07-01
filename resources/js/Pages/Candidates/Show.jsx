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

export default function Show({ candidate }) {
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
                                <dt className="font-medium text-gray-500">
                                    Грейд
                                </dt>
                                <dd className="text-gray-900">
                                    {candidate.grade
                                        ? GRADE_LABELS[candidate.grade]
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Локация
                                </dt>
                                <dd className="text-gray-900">
                                    {candidate.location || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Загрузил
                                </dt>
                                <dd className="text-gray-900">
                                    {candidate.uploader?.name}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Файл резюме
                                </dt>
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