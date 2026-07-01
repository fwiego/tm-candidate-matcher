import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const GRADE_LABELS = {
    junior: 'Junior',
    middle: 'Middle',
    senior: 'Senior',
    lead: 'Lead',
};

function CoverageBar({ percent }) {
    const color =
        percent >= 80
            ? 'bg-green-500'
            : percent >= 50
              ? 'bg-yellow-400'
              : 'bg-red-400';

    return (
        <div className="mt-2">
            <div className="flex items-center justify-between text-sm">
                <span className="font-medium text-gray-700">Покрытие требований</span>
                <span className="font-bold text-gray-900">{percent}%</span>
            </div>
            <div className="mt-1 h-3 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    className={`h-full rounded-full transition-all ${color}`}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}

export default function Show({ assessment }) {
    const mustReqs = assessment.requirements.filter((r) => r.type === 'must');
    const niceReqs = assessment.requirements.filter((r) => r.type === 'nice');

    const mustMatched = mustReqs.filter((r) => r.is_matched).length;
    const niceMatched = niceReqs.filter((r) => r.is_matched).length;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Результат сверки
                    </h2>
                    <Link
                        href={route('assessments.create', {
                            candidate_id: assessment.candidate.id,
                            request_id: assessment.request.id,
                        })}
                    >
                        <PrimaryButton>Повторить сверку</PrimaryButton>
                    </Link>
                </div>
            }
        >
            <Head title="Результат сверки" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <dl className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="font-medium text-gray-500">Кандидат</dt>
                                <dd className="text-gray-900">
                                    <Link
                                        href={route('candidates.show', assessment.candidate.id)}
                                        className="text-indigo-600 hover:underline"
                                    >
                                        {assessment.candidate.full_name}
                                    </Link>
                                    {assessment.candidate.grade && (
                                        <span className="ml-2 text-gray-400">
                                            {GRADE_LABELS[assessment.candidate.grade]}
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">Запрос</dt>
                                <dd className="text-gray-900">
                                    <Link
                                        href={route('requests.show', assessment.request.id)}
                                        className="text-indigo-600 hover:underline"
                                    >
                                        {assessment.request.position}
                                    </Link>
                                    {assessment.request.grade && (
                                        <span className="ml-2 text-gray-400">
                                            {GRADE_LABELS[assessment.request.grade]}
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">Выполнил</dt>
                                <dd className="text-gray-900">{assessment.calculated_by ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">Дата</dt>
                                <dd className="text-gray-900">{assessment.updated_at}</dd>
                            </div>
                        </dl>

                        <div className="mt-4 border-t border-gray-100 pt-4">
                            <CoverageBar percent={assessment.coverage_percent} />
                            <div className="mt-2 flex gap-4 text-xs text-gray-500">
                                <span>Must: {mustMatched}/{mustReqs.length}</span>
                                <span>Nice: {niceMatched}/{niceReqs.length}</span>
                            </div>
                        </div>
                    </div>

                    {mustReqs.length > 0 && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-sm font-semibold text-gray-700">
                                Обязательные требования (Must)
                            </h3>
                            <ul className="divide-y divide-gray-100">
                                {mustReqs.map((req) => (
                                    <li
                                        key={req.id}
                                        className="flex items-center justify-between py-2 text-sm"
                                    >
                                        <span className="text-gray-900">{req.technology}</span>
                                        <span className="flex items-center gap-3">
                                            <span className="text-xs text-gray-400">вес {req.weight}</span>
                                            {req.is_matched ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                    ✓ есть
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">
                                                    ✗ нет
                                                </span>
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {niceReqs.length > 0 && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-sm font-semibold text-gray-700">
                                Желательные требования (Nice to have)
                            </h3>
                            <ul className="divide-y divide-gray-100">
                                {niceReqs.map((req) => (
                                    <li
                                        key={req.id}
                                        className="flex items-center justify-between py-2 text-sm"
                                    >
                                        <span className="text-gray-900">{req.technology}</span>
                                        <span className="flex items-center gap-3">
                                            <span className="text-xs text-gray-400">вес {req.weight}</span>
                                            {req.is_matched ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                    ✓ есть
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600">
                                                    — нет
                                                </span>
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <Link
                        href={route('candidates.show', assessment.candidate.id)}
                        className="text-sm text-indigo-600 hover:text-indigo-900"
                    >
                        ← К карточке кандидата
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}