import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import RequestForm from './Partials/RequestForm';

export default function Create({ technologies, grades }) {
    const { data, setData, post, errors, processing } = useForm({
        position: '',
        description: '',
        grade: '',
        location: '',
        citizenship: '',
        needed_by: '',
        status: 'draft',
        requirements: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('requests.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Новый запрос
                </h2>
            }
        >
            <Head title="Новый запрос" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <RequestForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            technologies={technologies}
                            grades={grades}
                            statuses={['draft', 'open']}
                            submit={submit}
                            submitLabel="Создать"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}