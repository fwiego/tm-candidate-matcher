import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const GRADE_LABELS = {
    junior: 'Junior',
    middle: 'Middle',
    senior: 'Senior',
    lead: 'Lead',
};

export default function Create({ candidates, requests, preselect }) {
    const { data, setData, post, errors, processing } = useForm({
        candidate_id: preselect?.candidate_id ?? '',
        request_id: preselect?.request_id ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('assessments.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Новая сверка
                </h2>
            }
        >
            <Head title="Новая сверка" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="mb-6 text-sm text-gray-500">
                            Выберите кандидата и открытый запрос. Система
                            сравнит навыки кандидата с требованиями и
                            рассчитает процент покрытия. Повторная сверка
                            перезапишет предыдущий результат.
                        </p>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel
                                    htmlFor="candidate_id"
                                    value="Кандидат"
                                />
                                <select
                                    id="candidate_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.candidate_id}
                                    onChange={(e) =>
                                        setData('candidate_id', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Выберите кандидата</option>
                                    {candidates.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.full_name}
                                            {c.grade ? ` — ${GRADE_LABELS[c.grade]}` : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError className="mt-2" message={errors.candidate_id} />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="request_id"
                                    value="Запрос (вакансия)"
                                />
                                <select
                                    id="request_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.request_id}
                                    onChange={(e) =>
                                        setData('request_id', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Выберите запрос</option>
                                    {requests.map((r) => (
                                        <option key={r.id} value={r.id}>
                                            {r.position}
                                            {r.grade ? ` — ${GRADE_LABELS[r.grade]}` : ''}
                                        </option>
                                    ))}
                                </select>
                                {requests.length === 0 && (
                                    <p className="mt-1 text-xs text-amber-600">
                                        Нет открытых запросов. Сначала переведите запрос в статус "Открыт".
                                    </p>
                                )}
                                <InputError className="mt-2" message={errors.request_id} />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Выполняем сверку…' : 'Запустить сверку'}
                                </PrimaryButton>
                                <Link href={route('candidates.index')}>
                                    <SecondaryButton type="button">Отмена</SecondaryButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}