import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import UserForm from './Partials/UserForm';

export default function Create({ roles }) {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        email: '',
        password: '',
        roles: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Новый пользователь
                </h2>
            }
        >
            <Head title="Новый пользователь" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <UserForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            roles={roles}
                            submit={submit}
                            submitLabel="Создать"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}