import { Head, Link, router } from '@inertiajs/react';
import { Building2, FileText, Receipt, Users } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/status-badge';
import { dashboard } from '@/routes';
import { index as attendanceIndex } from '@/routes/attendance';
import { index as invoicesIndex } from '@/routes/invoices';
import { MONTHS } from '@/components/filter-bar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Stats = {
    active_clients: number;
    employees: number;
    invoices_due: number;
    total_billed_this_month: number;
};

type ClientRow = {
    id: number;
    name: string;
    unit_label: string | null;
    employees_count: number;
    client_status: string;
    employees_status: string;
    attendance_status: string;
    attendance_done: number;
    attendance_total: number;
    wages_status: string;
    wages_done: number;
    wages_total: number;
    invoice_status: string;
    invoice_count: number;
    payslip_status: string;
    payslip_done: number;
    payslip_total: number;
};

type Props = {
    stats: Stats;
    clients: ClientRow[];
    month: number;
    year: number;
};

export default function Dashboard({ stats, clients, month, year }: Props) {
    const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - 3 + i);
    const changePeriod = (params: { month?: string; year?: string }) => {
        router.get(dashboard().url, { month: params.month ?? month, year: params.year ?? year }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-primary text-xs font-semibold tracking-wide uppercase">
                                Active Clients
                            </CardTitle>
                            <Building2 className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-primary font-serif text-2xl font-semibold">
                                {stats.active_clients}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-primary text-xs font-semibold tracking-wide uppercase">
                                Employees
                            </CardTitle>
                            <Users className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-primary font-serif text-2xl font-semibold">
                                {stats.employees}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-primary text-xs font-semibold tracking-wide uppercase">
                                Invoices Due
                            </CardTitle>
                            <Receipt className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-primary font-serif text-2xl font-semibold">
                                {stats.invoices_due}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-primary text-xs font-semibold tracking-wide uppercase">
                                Billed This Month
                            </CardTitle>
                            <FileText className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-primary font-serif text-2xl font-semibold">
                                Rs.{' '}
                                {stats.total_billed_this_month.toLocaleString(
                                    'en-IN',
                                    { minimumFractionDigits: 2 },
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button asChild>
                        <Link href={attendanceIndex()}>Mark Attendance</Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={invoicesIndex()}>Generate Invoice</Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle>Clients &mdash; {MONTHS[month - 1]} {year}</CardTitle>
                            <div className="flex gap-2">
                                <Select value={String(month)} onValueChange={(value) => changePeriod({ month: value })}>
                                    <SelectTrigger className="w-32"><SelectValue /></SelectTrigger>
                                    <SelectContent>{MONTHS.map((name, index) => <SelectItem key={name} value={String(index + 1)}>{name}</SelectItem>)}</SelectContent>
                                </Select>
                                <Select value={String(year)} onValueChange={(value) => changePeriod({ year: value })}>
                                    <SelectTrigger className="w-24"><SelectValue /></SelectTrigger>
                                    <SelectContent>{years.map((value) => <SelectItem key={value} value={String(value)}>{value}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full table-fixed text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="w-[44%] py-2 pr-4 font-medium">Client</th>
                                            <th className="w-[11.2%] py-2 pr-4 text-center font-medium">Employees</th>
                                            <th className="w-[11.2%] py-2 pr-4 text-center font-medium">Attendance</th>
                                            <th className="w-[11.2%] py-2 pr-4 text-center font-medium">Wages</th>
                                            <th className="w-[11.2%] py-2 pr-4 text-center font-medium">Invoice</th>
                                            <th className="w-[11.2%] py-2 pr-4 text-center font-medium">Payslip</th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    {clients.map((client) => (
                                        <tr
                                            key={client.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="align-top py-2 pr-4">
                                                {client.name}
                                                {client.unit_label && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        - {client.unit_label}
                                                    </span>
                                                )}
                                                <div className="mt-1">
                                                    <StatusBadge status={client.client_status} />
                                                </div>
                                            </td>
                                            <td className="align-top py-2 pr-4 text-center">
                                                <div className="flex min-h-12 flex-col items-center gap-1">
                                                    <div className="font-medium leading-5">{client.employees_count}</div>
                                                    <StatusBadge status={client.employees_status} />
                                                </div>
                                            </td>
                                            <td className="align-top py-2 pr-4 text-center">
                                                <div className="flex min-h-12 flex-col items-center gap-1">
                                                    <div className="text-muted-foreground text-xs leading-5">
                                                        {client.attendance_done}/{client.attendance_total} days
                                                    </div>
                                                    <StatusBadge status={client.attendance_status} />
                                                </div>
                                            </td>
                                            <td className="align-top py-2 pr-4 text-center">
                                                <div className="flex min-h-12 flex-col items-center gap-1">
                                                    <div className="text-muted-foreground text-xs leading-5">
                                                        {client.wages_done}/{client.wages_total}
                                                    </div>
                                                    <StatusBadge status={client.wages_status} />
                                                </div>
                                            </td>
                                            <td className="align-top py-2 pr-4 text-center">
                                                <div className="flex min-h-12 flex-col items-center gap-1">
                                                    <div className="font-medium leading-5">{client.invoice_count}</div>
                                                    <StatusBadge status={client.invoice_status} />
                                                </div>
                                            </td>
                                            <td className="align-top py-2 pr-4 text-center">
                                                <div className="flex min-h-12 flex-col items-center gap-1">
                                                    <div className="text-muted-foreground text-xs leading-5">
                                                        {client.payslip_done}/{client.payslip_total}
                                                    </div>
                                                    <StatusBadge status={client.payslip_status} />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {clients.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="text-muted-foreground py-6 text-center"
                                            >
                                                No active clients yet.
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

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
