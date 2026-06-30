import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Upload() {
    const { data, setData, post, errors, processing, progress } = useForm({
        resume: null,
        grade: '',
        location: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('candidates.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Загрузить резюме
                </h2>
            }
        >
            <Head title="Загрузить резюме" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel
                                    htmlFor="resume"
                                    value="Файл резюме (PDF или DOCX)"
                                />
                                <input
                                    id="resume"
                                    type="file"
                                    accept=".pdf,.docx"
                                    className="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                                    onChange={(e) =>
                                        setData(
                                            'resume',
                                            e.target.files[0] ?? null,
                                        )
                                    }
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    ФИО кандидата будет определено
                                    автоматически из имени файла — его можно
                                    будет поправить после загрузки. Если
                                    кандидат с таким же именем уже существует,
                                    его карточка будет обновлена.
                                </p>
                                <InputError
                                    className="mt-2"
                                    message={errors.resume}
                                />
                                {progress && (
                                    <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                        <div
                                            className="h-full bg-indigo-600 transition-all"
                                            style={{
                                                width: `${progress.percentage}%`,
                                            }}
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel
                                        htmlFor="grade"
                                        value="Грейд (необязательно)"
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
                                        value="Локация (необязательно)"
                                    />
                                    <input
                                        id="location"
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.location}
                                        onChange={(e) =>
                                            setData(
                                                'location',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Город / страна"
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.location}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    {processing
                                        ? 'Обрабатываем…'
                                        : 'Загрузить и распознать'}
                                </PrimaryButton>
                                <Link href={route('candidates.index')}>
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