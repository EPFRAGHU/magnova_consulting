import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar, MONTHS } from '@/components/filter-bar';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SelectNative } from '@/components/ui/select-native';
import { dashboard } from '@/routes';
import { index, show, store } from '@/routes/invoices';

type ClientOption = { id: number; name: string; unit_label: string | null };

type InvoiceRow = {
    id: number;
    invoice_no: string;
    invoice_date: string;
    month: number;
    year: number;
    grand_total: string;
    status: string;
    client: { id: number; name: string; unit_label: string | null };
};

export default function InvoicesIndex({
    invoices,
    clients,
    client_id: clientId,
}: {
    invoices: InvoiceRow[];
    clients: ClientOption[];
    client_id: number | null;
}) {
    const now = new Date();
    const [genClient, setGenClient] = useState<string>(
        clients[0] ? String(clients[0].id) : '',
    );
    const wageMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const [genMonth, setGenMonth] = useState(String(wageMonth.getMonth() + 1));
    const [genYear, setGenYear] = useState(String(wageMonth.getFullYear()));
    const [processing, setProcessing] = useState(false);

    const generate = () => {
        if (!genClient) return;
        setProcessing(true);
        router.post(
            store().url,
            { client_id: genClient, month: genMonth, year: genYear },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <>
            <Head title="Invoices" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Invoices"
                    description="Monthly client bills generated from saved wages"
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">
                            Generate Invoice
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-end gap-2">
                        <SelectNative
                            value={genClient}
                            onChange={(e) => setGenClient(e.target.value)}
                            className="w-56"
                        >
                            {clients.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                    {c.unit_label ? ` - ${c.unit_label}` : ''}
                                </option>
                            ))}
                        </SelectNative>
                        <SelectNative
                            value={genMonth}
                            onChange={(e) => setGenMonth(e.target.value)}
                            className="w-36"
                        >
                            {MONTHS.map((m, i) => (
                                <option key={m} value={i + 1}>
                                    {m}
                                </option>
                            ))}
                        </SelectNative>
                        <SelectNative
                            value={genYear}
                            onChange={(e) => setGenYear(e.target.value)}
                            className="w-24"
                        >
                            {Array.from(
                                { length: 6 },
                                (_, i) => now.getFullYear() - 3 + i,
                            ).map((y) => (
                                <option key={y} value={y}>
                                    {y}
                                </option>
                            ))}
                        </SelectNative>
                        <Button
                            onClick={generate}
                            disabled={processing || !genClient}
                        >
                            Generate Invoice
                        </Button>
                    </CardContent>
                </Card>

                <FilterBar
                    url={index().url}
                    clients={clients}
                    clientId={clientId}
                    showPeriod={false}
                />

                <Card>
                    <CardContent className="px-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="px-6 py-2 font-medium">
                                            Invoice No.
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Client
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Period
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Amount
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Status
                                        </th>
                                        <th className="px-6 py-2 font-medium" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoices.map((invoice) => (
                                        <tr
                                            key={invoice.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-6 py-2">
                                                {invoice.invoice_no}
                                            </td>
                                            <td className="px-6 py-2">
                                                {invoice.client.name}
                                                {invoice.client.unit_label
                                                    ? ` - ${invoice.client.unit_label}`
                                                    : ''}
                                            </td>
                                            <td className="px-6 py-2">
                                                {MONTHS[invoice.month - 1]}{' '}
                                                {invoice.year}
                                            </td>
                                            <td className="px-6 py-2">
                                                Rs.{' '}
                                                {Number(
                                                    invoice.grand_total,
                                                ).toLocaleString('en-IN', {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </td>
                                            <td className="px-6 py-2">
                                                <StatusBadge
                                                    status={invoice.status}
                                                />
                                            </td>
                                            <td className="px-6 py-2 text-right">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={show(invoice.id)}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {invoices.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="text-muted-foreground px-6 py-6 text-center"
                                            >
                                                No invoices generated yet.
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

InvoicesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Invoices', href: index() },
    ],
};
