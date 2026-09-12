import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar } from '@/components/filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { index, register, store } from '@/routes/wages';

type ClientOption = { id: number; name: string; unit_label: string | null };

type Row = {
    employee_id: number;
    name: string;
    employee_code: string | null;
    working_days: number;
    rate_per_day: number;
    bonus: number;
    saved: boolean;
};

export default function WagesIndex({
    rows,
    clients,
    client_id: clientId,
    month,
    year,
    rates,
}: {
    rows: Row[];
    clients: ClientOption[];
    client_id: number | null;
    month: number;
    year: number;
    rates: { epf_employee_pct: number; esic_employee_pct: number };
}) {
    const [bonuses, setBonuses] = useState<Record<number, number>>(
        Object.fromEntries(rows.map((r) => [r.employee_id, r.bonus])),
    );
    const [processing, setProcessing] = useState(false);

    const compute = (row: Row) => {
        const gross = row.working_days * row.rate_per_day;
        const bonus = bonuses[row.employee_id] ?? 0;
        const epf = round2((gross * rates.epf_employee_pct) / 100);
        const esic = round2((gross * rates.esic_employee_pct) / 100);
        const net = round2(gross + bonus - epf - esic);
        return { gross: round2(gross), epf, esic, net };
    };

    const save = () => {
        setProcessing(true);
        router.post(
            store().url,
            {
                month,
                year,
                rows: rows.map((r) => ({
                    employee_id: r.employee_id,
                    bonus: bonuses[r.employee_id] ?? 0,
                })),
            },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Wages" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Wages"
                        description="Auto-computed from attendance, correct bonus before saving"
                    />
                    <div className="flex gap-2">
                        {clientId && (
                            <Button asChild variant="outline">
                                <a
                                    href={`${register().url}?${new URLSearchParams({ client_id: String(clientId), month: String(month), year: String(year) }).toString()}`}
                                >
                                    <Download /> Register of Wages
                                </a>
                            </Button>
                        )}
                        <Button
                            onClick={save}
                            disabled={processing || rows.length === 0}
                        >
                            Save Wages for Month
                        </Button>
                    </div>
                </div>

                <FilterBar
                    url={index().url}
                    clients={clients}
                    clientId={clientId}
                    month={month}
                    year={year}
                />

                <Card>
                    <CardContent className="px-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="px-4 py-2 font-medium">
                                            Employee
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Working Days
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Rate/Day
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Gross
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Bonus
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            EPF (12%)
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            ESIC (0.75%)
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Net Salary
                                        </th>
                                        <th className="px-4 py-2 font-medium" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row) => {
                                        const { gross, epf, esic, net } =
                                            compute(row);
                                        return (
                                            <tr
                                                key={row.employee_id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-2">
                                                    {row.name}
                                                    {row.employee_code && (
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            ({row.employee_code}
                                                            )
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {row.working_days}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {row.rate_per_day.toFixed(
                                                        2,
                                                    )}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {gross.toFixed(2)}
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        className="h-8 w-24"
                                                        value={
                                                            bonuses[
                                                                row.employee_id
                                                            ] ?? 0
                                                        }
                                                        onChange={(e) =>
                                                            setBonuses(
                                                                (prev) => ({
                                                                    ...prev,
                                                                    [row.employee_id]:
                                                                        parseFloat(
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        ) || 0,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-2">
                                                    {epf.toFixed(2)}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {esic.toFixed(2)}
                                                </td>
                                                <td className="px-4 py-2 font-medium">
                                                    {net.toFixed(2)}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {row.saved && (
                                                        <Badge variant="outline">
                                                            Saved
                                                        </Badge>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={9}
                                                className="text-muted-foreground px-4 py-6 text-center"
                                            >
                                                No active employees for this
                                                selection.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function round2(n: number) {
    return Math.round(n * 100) / 100;
}

WagesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Wages', href: index() },
    ],
};
