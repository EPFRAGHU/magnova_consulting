import { router } from '@inertiajs/react';
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
    clients,
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
        <div className="flex flex-wrap items-center gap-2">
            {showClient && (
                <Select
                    value={clientId ? String(clientId) : 'all'}
                    onValueChange={(v) =>
                        go({ client_id: v === 'all' ? undefined : v })
                    }
                >
                    <SelectTrigger className="w-56">
                        <SelectValue placeholder="All clients" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All clients</SelectItem>
                        {clients?.map((c) => (
                            <SelectItem key={c.id} value={String(c.id)}>
                                {c.name}
                                {c.unit_label ? ` - ${c.unit_label}` : ''}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {showPeriod && (
                <>
                    <Select
                        value={String(month)}
                        onValueChange={(v) => go({ month: v })}
                    >
                        <SelectTrigger className="w-36">
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
                        <SelectTrigger className="w-24">
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
