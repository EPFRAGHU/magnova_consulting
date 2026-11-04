import { Link, router, usePage } from '@inertiajs/react';
import { index as clientsIndex } from '@/routes/clients';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const MONTHS = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

type ClientOption = { id: number; name: string; unit_label: string | null };

export function FilterBar({
    url,
    clientId,
    month,
    year,
    showClient = true,
    showPeriod = true,
}: {
    url: string;
    clients?: ClientOption[];
    clientId?: number | null;
    month?: number;
    year?: number;
    showClient?: boolean;
    showPeriod?: boolean;
}) {
    const { loadedClient } = usePage().props;
    const years = Array.from(
        { length: 6 },
        (_, i) => new Date().getFullYear() - 3 + i,
    );

    const go = (params: Record<string, string | number | undefined>) => {
        router.get(
            url,
            {
                client_id: clientId ?? undefined,
                month,
                year,
                ...params,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <div className="bg-card flex flex-wrap items-center gap-2 rounded-2xl border p-3 shadow-sm">
            {showClient && (
                <div className="flex items-center gap-2 text-sm">
                    <span className="bg-muted rounded-xl border px-3 py-1.5 font-medium">
                        {loadedClient
                            ? `${loadedClient.name}${loadedClient.unit_label ? ` - ${loadedClient.unit_label}` : ''}`
                            : 'No client'}
                    </span>
                    <Link
                        href={clientsIndex()}
                        className="text-xs text-blue-700 hover:underline dark:text-blue-300"
                    >
                        Change client
                    </Link>
                </div>
            )}

            {showPeriod && (
                <>
                    <Select
                        value={String(month)}
                        onValueChange={(v) => go({ month: v })}
                    >
                        <SelectTrigger className="w-36 rounded-xl">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {MONTHS.map((m, i) => (
                                <SelectItem key={m} value={String(i + 1)}>
                                    {m}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={String(year)}
                        onValueChange={(v) => go({ year: v })}
                    >
                        <SelectTrigger className="w-24 rounded-xl">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((y) => (
                                <SelectItem key={y} value={String(y)}>
                                    {y}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </>
            )}
        </div>
    );
}

export { MONTHS };
