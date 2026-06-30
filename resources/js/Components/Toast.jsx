import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Toast() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(null);

    useEffect(() => {
        if (flash?.success) {
            setVisible({ type: 'success', message: flash.success });
        } else if (flash?.error) {
            setVisible({ type: 'error', message: flash.error });
        } else {
            return;
        }

        const timer = setTimeout(() => setVisible(null), 4000);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash?.success, flash?.error]);

    if (!visible) {
        return null;
    }

    const isError = visible.type === 'error';

    return (
        <div className="fixed bottom-4 right-4 z-50 transition-opacity duration-200">
            <div
                className={`flex items-center gap-2 rounded-md px-4 py-3 text-sm font-medium text-white shadow-lg ${
                    isError ? 'bg-red-600' : 'bg-green-600'
                }`}
            >
                {visible.message}
                <button
                    onClick={() => setVisible(null)}
                    className="ml-2 text-white/80 hover:text-white"
                    aria-label="Закрыть"
                >
                    ✕
                </button>
            </div>
        </div>
    );
}