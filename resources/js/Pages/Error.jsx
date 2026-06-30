import { Head, Link, usePage } from '@inertiajs/react';

const MESSAGES = {
    403: {
        title: 'Доступ запрещён',
        description: 'У вас нет прав для просмотра этой страницы.',
    },
    404: {
        title: 'Страница не найдена',
        description: 'Запрошенная страница не существует.',
    },
    419: {
        title: 'Сессия истекла',
        description: 'Пожалуйста, обновите страницу и попробуйте снова.',
    },
    429: {
        title: 'Слишком много запросов',
        description: 'Подождите немного и попробуйте снова.',
    },
    500: {
        title: 'Ошибка сервера',
        description: 'Что-то пошло не так. Попробуйте позже.',
    },
    503: {
        title: 'Сервис недоступен',
        description: 'Сайт временно на обслуживании.',
    },
};

export default function Error({ status, message }) {
    const { auth } = usePage().props;
    const info = MESSAGES[status] ?? {
        title: 'Произошла ошибка',
        description: 'Попробуйте обновить страницу.',
    };

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">
            <Head title={info.title} />

            <div className="w-full max-w-md rounded-lg bg-white p-8 text-center shadow-sm">
                <div className="mb-2 text-6xl font-bold text-gray-300">
                    {status}
                </div>
                <h1 className="mb-2 text-xl font-semibold text-gray-800">
                    {info.title}
                </h1>
                <p className="mb-6 text-sm text-gray-500">
                    {message || info.description}
                </p>

                <div className="flex justify-center gap-3">
                    <Link
                        href={auth?.user ? route('dashboard') : route('login')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        На главную
                    </Link>

                    {auth?.user && (
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Выйти
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}