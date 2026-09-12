import { Head, Link } from '@inertiajs/react';
import { Download, Pencil } from 'lucide-react';
import Heading from '@/components/heading';
import { MONTHS } from '@/components/filter-bar';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { edit, index, pdf } from '@/routes/invoices';

type LineItem = {
    id: number;
    working_days: string;
    rate_per_day: string;
    wage_amount: string;
    employee: { id: number; name: string; designation: string | null };
};

type Invoice = {
    id: number;
    invoice_no: string;
    ref_no: string | null;
    invoice_date: string;
    due_date: string | null;
    place_of_supply: string | null;
    reverse_charge: boolean;
    month: number;
    year: number;
    rate_per_day: string;
    no_of_persons: number;
    no_of_duties: string;
    base_amount: string;
    epf_employer: string;
    epf_admin: string;
    edli_cont: string;
    esi_employer: string;
    taxable_value: string;
    service_charge: string;
    cgst: string;
    sgst: string;
    grand_total: string;
    status: string;
    client: {
        id: number;
        name: string;
        unit_label: string | null;
        gemc_no: string | null;
    };
    line_items: LineItem[];
};

function money(v: string | number) {
    return Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

export default function InvoiceShow({ invoice }: { invoice: Invoice }) {
    return (
        <>
            <Head title={`Invoice ${invoice.invoice_no}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={`Invoice ${invoice.invoice_no}`}
                        description={`${invoice.client.name}${invoice.client.unit_label ? ' - ' + invoice.client.unit_label : ''} · ${MONTHS[invoice.month - 1]} ${invoice.year}`}
                    />
                    <div className="flex items-center gap-2">
                        <StatusBadge status={invoice.status} />
                        <Button asChild variant="outline">
                            <Link href={edit(invoice.id)}>
                                <Pencil /> Edit Details
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <a href={pdf(invoice.id).url}>
                                <Download /> Download PDF
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">
                                Invoice Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full text-sm">
                                <tbody>
                                    <Row
                                        label="Ref No."
                                        value={invoice.ref_no ?? '-'}
                                    />
                                    <Row
                                        label="Invoice Date"
                                        value={new Date(
                                            invoice.invoice_date,
                                        ).toLocaleDateString('en-IN')}
                                    />
                                    <Row
                                        label="Due Date"
                                        value={
                                            invoice.due_date
                                                ? new Date(
                                                      invoice.due_date,
                                                  ).toLocaleDateString('en-IN')
                                                : '-'
                                        }
                                    />
                                    <Row
                                        label="Place of Supply"
                                        value={invoice.place_of_supply ?? '-'}
                                    />
                                    <Row
                                        label="Reverse Charge"
                                        value={
                                            invoice.reverse_charge
                                                ? 'Yes'
                                                : 'No'
                                        }
                                    />
                                    <Row
                                        label="GEMC No."
                                        value={invoice.client.gemc_no ?? '-'}
                                    />
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">
                                Billing Summary
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full text-sm">
                                <tbody>
                                    <Row
                                        label="Rate per Person per Day"
                                        value={money(invoice.rate_per_day)}
                                    />
                                    <Row
                                        label="No. of Persons"
                                        value={String(invoice.no_of_persons)}
                                    />
                                    <Row
                                        label="Total Duties (Man-days)"
                                        value={money(invoice.no_of_duties)}
                                    />
                                    <Row
                                        label="Base Amount"
                                        value={money(invoice.base_amount)}
                                    />
                                    <Row
                                        label="EPF Employer (13%)"
                                        value={money(invoice.epf_employer)}
                                    />
                                    <Row
                                        label="EPF Admin Charges (0.5%)"
                                        value={money(invoice.epf_admin)}
                                    />
                                    <Row
                                        label="EDLI Cont. (0.5%)"
                                        value={money(invoice.edli_cont)}
                                    />
                                    <Row
                                        label="ESI Employer (3.25%)"
                                        value={money(invoice.esi_employer)}
                                    />
                                    <Row
                                        label="Service Charge (3.85%)"
                                        value={money(invoice.service_charge)}
                                    />
                                    <Row
                                        label="Taxable Value"
                                        value={money(invoice.taxable_value)}
                                        bold
                                    />
                                    <Row
                                        label="CGST (9%)"
                                        value={money(invoice.cgst)}
                                    />
                                    <Row
                                        label="SGST (9%)"
                                        value={money(invoice.sgst)}
                                    />
                                    <Row
                                        label="Grand Total"
                                        value={`Rs. ${money(invoice.grand_total)}`}
                                        bold
                                        large
                                    />
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">
                            Employee Breakup
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="px-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="px-6 py-2 font-medium">
                                            Employee
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Designation
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Working Days
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Rate/Day
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Wage Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoice.line_items.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-6 py-2">
                                                {item.employee.name}
                                            </td>
                                            <td className="px-6 py-2">
                                                {item.employee.designation ??
                                                    '-'}
                                            </td>
                                            <td className="px-6 py-2">
                                                {item.working_days}
                                            </td>
                                            <td className="px-6 py-2">
                                                {money(item.rate_per_day)}
                                            </td>
                                            <td className="px-6 py-2">
                                                {money(item.wage_amount)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Row({
    label,
    value,
    bold,
    large,
}: {
    label: string;
    value: string;
    bold?: boolean;
    large?: boolean;
}) {
    return (
        <tr className={bold ? 'font-semibold' : undefined}>
            <td className={`py-1.5 ${large ? 'text-base' : ''}`}>{label}</td>
            <td className={`py-1.5 text-right ${large ? 'text-base' : ''}`}>
                {value}
            </td>
        </tr>
    );
}

InvoiceShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Invoices', href: index() },
    ],
};
