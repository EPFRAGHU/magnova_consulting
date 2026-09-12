import { Head } from '@inertiajs/react';
import { Download, FileText } from 'lucide-react';
import Heading from '@/components/heading';
import { FilterBar } from '@/components/filter-bar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { bulk, index, show } from '@/routes/payslips';

type ClientOption = { id: number; name: string; unit_label: string | null };

type WageRow = {
    id: number;
    net_salary: string;
    employee: { id: number; name: string; employee_code: string | null };
};

export default function PayslipsIndex({
    wages,
    clients,
    client_id: clientId,
    month,
    year,
}: {
    wages: WageRow[];
    clients: ClientOption[];
    client_id: number | null;
    month: number;
    year: number;
}) {
    const bulkUrl = `${bulk().url}?${new URLSearchParams({
        ...(clientId ? { client_id: String(clientId) } : {}),
        month: String(month),
        year: String(year),
    }).toString()}`;

    return (
        <>
            <Head title="Payslips" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Payslips"
                        description="Download individual or bulk payslips for a client and month"
                    />
                    <Button asChild disabled={wages.length === 0}>
                        <a href={bulkUrl}>
                            <FileText /> Download All as One PDF
                        </a>
                    </Button>
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
                                        <th className="px-6 py-2 font-medium">
                                            Employee
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Net Salary
                                        </th>
                                        <th className="px-6 py-2 font-medium" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {wages.map((wage) => (
                                        <tr
                                            key={wage.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-6 py-2">
                                                {wage.employee.name}
                                                {wage.employee
                                                    .employee_code && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        (
                                                        {
                                                            wage.employee
                                                                .employee_code
                                                        }
                                                        )
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-2">
                                                Rs.{' '}
                                                {Number(
                                                    wage.net_salary,
                                                ).toLocaleString('en-IN', {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </td>
                                            <td className="px-6 py-2 text-right">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <a
                                                        href={show(wage.id).url}
                                                        title="Download payslip"
                                                    >
                                                        <Download />
                                                    </a>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {wages.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="text-muted-foreground px-6 py-6 text-center"
                                            >
                                                No wages saved for this client
                                                and month yet.
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

PayslipsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Payslips', href: index() },
    ],
};
