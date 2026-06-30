import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import UserForm from './Partials/UserForm';

export default function Edit({ user, roles }) {
    const { data, setData, put, errors, processing } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        roles: user.role_ids,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.users.update', user.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Редактирование пользователя
                </h2>
            }
        >
            <Head title="Редактирование пользователя" />

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
                            submitLabel="Сохранить"
                            isEdit
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}