import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

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

export default function Show({ request, canEdit }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {request.position}
                    </h2>
                    {canEdit && (
                        <Link href={route('requests.edit', request.id)}>
                            <PrimaryButton>Редактировать</PrimaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title={request.position} />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="mb-4 flex items-center gap-3">
                            <span
                                className={`inline-flex rounded-full px-3 py-1 text-sm font-medium ${STATUS_COLORS[request.status]}`}
                            >
                                {STATUS_LABELS[request.status]}
                            </span>
                            <span className="text-sm text-gray-500">
                                {GRADE_LABELS[request.grade]}
                            </span>
                        </div>

                        {request.description && (
                            <p className="mb-4 whitespace-pre-line text-sm text-gray-700">
                                {request.description}
                            </p>
                        )}

                        <dl className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Локация
                                </dt>
                                <dd className="text-gray-900">
                                    {request.location || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Гражданство
                                </dt>
                                <dd className="text-gray-900">
                                    {request.citizenship || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Нужен к дате
                                </dt>
                                <dd className="text-gray-900">
                                    {request.needed_by || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-gray-500">
                                    Создал
                                </dt>
                                <dd className="text-gray-900">
                                    {request.creator?.name}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-4 text-sm font-semibold text-gray-700">
                            Требования
                        </h3>

                        {request.requirements?.length > 0 ? (
                            <ul className="divide-y divide-gray-100">
                                {request.requirements.map((req) => (
                                    <li
                                        key={req.id}
                                        className="flex items-center justify-between py-2 text-sm"
                                    >
                                        <span className="text-gray-900">
                                            {req.technology.name}
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    req.type === 'must'
                                                        ? 'bg-red-50 text-red-700'
                                                        : 'bg-blue-50 text-blue-700'
                                                }`}
                                            >
                                                {req.type === 'must'
                                                    ? 'Must'
                                                    : 'Nice to have'}
                                            </span>
                                            <span className="text-gray-400">
                                                вес {req.weight}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-gray-400">
                                Требования не добавлены.
                            </p>
                        )}
                    </div>

                    <Link
                        href={route('requests.index')}
                        className="text-sm text-indigo-600 hover:text-indigo-900"
                    >
                        ← Назад к списку
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}