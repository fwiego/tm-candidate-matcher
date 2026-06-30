import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import TechnologyForm from './Partials/TechnologyForm';

export default function Edit({ technology, groups }) {
    const { data, setData, put, errors, processing } = useForm({
        name: technology.name,
        group: technology.group ?? '',
        synonyms: technology.synonyms ?? [],
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.technologies.update', technology.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Редактирование технологии
                </h2>
            }
        >
            <Head title="Редактирование технологии" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <TechnologyForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            groups={groups}
                            submit={submit}
                            submitLabel="Сохранить"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}