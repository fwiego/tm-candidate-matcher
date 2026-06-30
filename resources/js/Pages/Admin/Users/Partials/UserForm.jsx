import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function UserForm({
    data,
    setData,
    errors,
    processing,
    roles,
    submit,
    submitLabel,
    isEdit = false,
}) {
    const toggleRole = (roleId) => {
        if (data.roles.includes(roleId)) {
            setData(
                'roles',
                data.roles.filter((id) => id !== roleId),
            );
        } else {
            setData('roles', [...data.roles, roleId]);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="name" value="Имя" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    isFocused
                    autoComplete="name"
                />
                <InputError className="mt-2" message={errors.name} />
            </div>

            <div>
                <InputLabel htmlFor="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    className="mt-1 block w-full"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                    autoComplete="username"
                />
                <InputError className="mt-2" message={errors.email} />
            </div>

            <div>
                <InputLabel
                    htmlFor="password"
                    value={
                        isEdit
                            ? 'Новый пароль (оставьте пустым, чтобы не менять)'
                            : 'Пароль'
                    }
                />
                <TextInput
                    id="password"
                    type="password"
                    className="mt-1 block w-full"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    required={!isEdit}
                    autoComplete="new-password"
                />
                <InputError className="mt-2" message={errors.password} />
            </div>

            <div>
                <InputLabel value="Роли" />
                <div className="mt-2 space-y-2">
                    {roles.map((role) => (
                        <label
                            key={role.id}
                            className="flex items-center gap-2"
                        >
                            <input
                                type="checkbox"
                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                checked={data.roles.includes(role.id)}
                                onChange={() => toggleRole(role.id)}
                            />
                            <span className="text-sm text-gray-700">
                                {role.name}
                            </span>
                        </label>
                    ))}
                </div>
                <InputError className="mt-2" message={errors.roles} />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>
                    {submitLabel}
                </PrimaryButton>
                <Link href={route('admin.users.index')}>
                    <SecondaryButton type="button">Отмена</SecondaryButton>
                </Link>
            </div>
        </form>
    );
}