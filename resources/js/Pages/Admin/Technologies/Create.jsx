import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import TechnologyForm from './Partials/TechnologyForm';

export default function Create({ groups }) {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        group: '',
        synonyms: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.technologies.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Новая технология
                </h2>
            }
        >
            <Head title="Новая технология" />

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
                            submitLabel="Создать"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}