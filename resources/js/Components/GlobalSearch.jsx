import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const GRADE_LABELS = {
    junior: 'Junior',
    middle: 'Middle',
    senior: 'Senior',
    lead: 'Lead',
};

const STATUS_LABELS = {
    draft: 'Черновик',
    open: 'Открыт',
    closed: 'Закрыт',
};

function ResultSection({ title, items, onSelect }) {
    if (!items?.length) return null;

    return (
        <div>
            <div className="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
                {title}
            </div>
            {items.map((item) => (
                <button
                    key={item.id}
                    type="button"
                    onClick={() => onSelect(item.url)}
                    className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"
                >
                    <span className="font-medium text-gray-900">
                        {item.label}
                    </span>
                    {item.sublabel && (
                        <span className="ml-2 shrink-0 text-xs text-gray-400">
                            {GRADE_LABELS[item.sublabel] ??
                                STATUS_LABELS[item.sublabel] ??
                                item.sublabel}
                        </span>
                    )}
                </button>
            ))}
        </div>
    );
}

export default function GlobalSearch() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState(null);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const inputRef = useRef(null);
    const containerRef = useRef(null);
    const debounceRef = useRef(null);

    useEffect(() => {
        if (query.length < 2) {
            setResults(null);
            setOpen(false);
            return;
        }

        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            setLoading(true);
            try {
                const res = await fetch(
                    route('search') + '?q=' + encodeURIComponent(query),
                    {
                        headers: { Accept: 'application/json' },
                    },
                );
                const data = await res.json();
                setResults(data);
                setOpen(true);
            } catch {
                setResults(null);
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(debounceRef.current);
    }, [query]);

    useEffect(() => {
        const handler = (e) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(e.target)
            ) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const handleSelect = (url) => {
        setQuery('');
        setOpen(false);
        router.visit(url);
    };

    const hasResults =
        results &&
        (results.candidates?.length ||
            results.requests?.length ||
            results.technologies?.length);

    return (
        <div ref={containerRef} className="relative hidden w-64 sm:block">
            <div className="relative">
                <svg
                    className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={1.5}
                    stroke="currentColor"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.197 5.197a7.5 7.5 0 0 0 10.606 10.606Z"
                    />
                </svg>
                <input
                    ref={inputRef}
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={() => results && setOpen(true)}
                    placeholder="Поиск..."
                    className="w-full rounded-md border-gray-300 py-1.5 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                {loading && (
                    <svg
                        className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-indigo-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                        />
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"
                        />
                    </svg>
                )}
            </div>

            {open && (
                <div className="absolute left-0 top-full z-50 mt-1 w-80 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg">
                    {hasResults ? (
                        <div className="divide-y divide-gray-100">
                            <ResultSection
                                title="Кандидаты"
                                items={results.candidates}
                                onSelect={handleSelect}
                            />
                            <ResultSection
                                title="Запросы"
                                items={results.requests}
                                onSelect={handleSelect}
                            />
                            <ResultSection
                                title="Технологии"
                                items={results.technologies}
                                onSelect={handleSelect}
                            />
                        </div>
                    ) : (
                        <div className="px-3 py-4 text-center text-sm text-gray-400">
                            Ничего не найдено
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}