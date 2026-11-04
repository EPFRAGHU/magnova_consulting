import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import { DeleteButton } from '@/components/delete-button';
import Heading from '@/components/heading';
import { FilterBar } from '@/components/filter-bar';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, destroy, edit, index } from '@/routes/employees';

type ClientOption = { id: number; name: string; unit_label: string | null };

type EmployeeRow = {
    id: number;
    employee_code: string | null;
    name: string;
    gender: 'male' | 'female' | null;
    designation: string | null;
    skill_category: string;
    rate_per_day: string;
    status: string;
    client: { id: number; name: string; unit_label: string | null };
};

export default function EmployeesIndex({
    employees,
    clients,
    client_id: clientId,
}: {
    employees: EmployeeRow[];
    clients: ClientOption[];
    client_id: number | null;
}) {
    return (
        <>
            <Head title="Employees" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Employees"
                        description="Manpower deployed across all client establishments"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Add Employee
                        </Link>
                    </Button>
                </div>

                <FilterBar
                    url={index().url}
                    clients={clients}
                    clientId={clientId}
                    showPeriod={false}
                />

                <Card className="overflow-hidden rounded-2xl py-0 shadow-sm">
                    <CardContent className="px-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-muted/50 text-muted-foreground border-b text-left text-xs font-semibold tracking-wider uppercase">
                                        <th className="px-6 py-3.5">Code</th>
                                        <th className="px-6 py-3.5">Name</th>
                                        <th className="px-6 py-3.5">M/F</th>
                                        <th className="px-6 py-3.5">Client</th>
                                        <th className="px-6 py-3.5">
                                            Designation
                                        </th>
                                        <th className="px-6 py-3.5">
                                            Category
                                        </th>
                                        <th className="px-6 py-3.5">
                                            Rate/Day
                                        </th>
                                        <th className="px-6 py-3.5">Status</th>
                                        <th className="px-6 py-3.5" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {employees.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className="hover:bg-muted/40 border-b transition-colors last:border-0"
                                        >
                                            <td className="px-6 py-2">
                                                {employee.employee_code ?? '-'}
                                            </td>
                                            <td className="px-6 py-2">
                                                {employee.name}
                                            </td>
                                            <td className="px-6 py-2">
                                                {employee.gender === 'male'
                                                    ? 'M'
                                                    : employee.gender ===
                                                        'female'
                                                      ? 'F'
                                                      : '-'}
                                            </td>
                                            <td className="px-6 py-2">
                                                {employee.client.name}
                                                {employee.client.unit_label
                                                    ? ` - ${employee.client.unit_label}`
                                                    : ''}
                                            </td>
                                            <td className="px-6 py-2">
                                                {employee.designation ?? '-'}
                                            </td>
                                            <td className="px-6 py-2 capitalize">
                                                {employee.skill_category.replace(
                                                    '_',
                                                    '-',
                                                )}
                                            </td>
                                            <td className="px-6 py-2">
                                                {employee.rate_per_day}
                                            </td>
                                            <td className="px-6 py-2">
                                                <StatusBadge
                                                    status={employee.status}
                                                />
                                            </td>
                                            <td className="px-6 py-2 text-right whitespace-nowrap">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-muted-foreground hover:text-indigo-600"
                                                >
                                                    <Link
                                                        href={edit(employee.id)}
                                                        title="Edit employee"
                                                        aria-label={`Edit ${employee.name}`}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <DeleteButton
                                                    deleteUrl={
                                                        destroy(employee.id).url
                                                    }
                                                    resourceName={employee.name}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                    {employees.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={9}
                                                className="text-muted-foreground px-6 py-6 text-center"
                                            >
                                                No employees found.
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

EmployeesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Employees', href: index() },
    ],
};
