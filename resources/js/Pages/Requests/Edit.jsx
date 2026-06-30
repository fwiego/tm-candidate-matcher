import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import RequestForm from './Partials/RequestForm';

const STATUS_ORDER = ['draft', 'open', 'closed'];

export default function Edit({ request, technologies, grades }) {
    const { data, setData, put, errors, processing } = useForm({
        position: request.position,
        description: request.description ?? '',
        grade: request.grade,
        location: request.location ?? '',
        citizenship: request.citizenship ?? '',
        needed_by: request.needed_by ?? '',
        status: request.status,
        requirements: request.requirements,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('requests.update', request.id));
    };

    // Status can only move forward: only show current status and statuses after it.
    const allowedStatuses = STATUS_ORDER.slice(
        STATUS_ORDER.indexOf(request.status),
    );

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Редактирование запроса
                </h2>
            }
        >
            <Head title="Редактирование запроса" />

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
                            statuses={allowedStatuses}
                            submit={submit}
                            submitLabel="Сохранить"
                            isEdit
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}