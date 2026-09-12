import { Head, Link } from '@inertiajs/react';
import { Building2, FileText, Receipt, Users } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/status-badge';
import { dashboard } from '@/routes';
import { index as attendanceIndex } from '@/routes/attendance';
import { index as invoicesIndex } from '@/routes/invoices';
import { MONTHS } from '@/components/filter-bar';

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
    status: string;
};

type Props = {
    stats: Stats;
    clients: ClientRow[];
    month: number;
    year: number;
};

export default function Dashboard({ stats, clients, month, year }: Props) {
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
                        <CardTitle>
                            Clients &mdash; {MONTHS[month - 1]} {year}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="py-2 pr-4 font-medium">
                                            Client
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Employees
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Billing Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {clients.map((client) => (
                                        <tr
                                            key={client.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-2 pr-4">
                                                {client.name}
                                                {client.unit_label && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        - {client.unit_label}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-2 pr-4">
                                                {client.employees_count}
                                            </td>
                                            <td className="py-2 pr-4">
                                                <StatusBadge
                                                    status={client.status}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                    {clients.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
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
