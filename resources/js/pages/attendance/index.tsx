import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar } from '@/components/filter-bar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index, sheet, update } from '@/routes/attendance';

type ClientOption = { id: number; name: string; unit_label: string | null };
type Employee = { id: number; name: string; employee_code: string | null };

type AttendanceMap = Record<number, Record<number, string>>;

const CYCLE = ['present', 'absent', 'holiday', 'leave', 'sunday'] as const;

const CELL_STYLE: Record<string, string> = {
    present:
        'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    sunday: 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    absent: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
    holiday:
        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    leave: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
};

const CELL_LABEL: Record<string, string> = {
    present: 'P',
    absent: 'A',
    holiday: 'H',
    leave: 'L',
    sunday: 'S',
};

export default function AttendanceIndex({
    employees,
    attendance,
    clients,
    client_id: clientId,
    month,
    year,
    days_in_month: daysInMonth,
}: {
    employees: Employee[];
    attendance: AttendanceMap;
    clients: ClientOption[];
    client_id: number | null;
    month: number;
    year: number;
    days_in_month: number;
}) {
    const [grid, setGrid] = useState<AttendanceMap>(attendance ?? {});
    const days = useMemo(
        () => Array.from({ length: daysInMonth }, (_, i) => i + 1),
        [daysInMonth],
    );

    const cycle = (employeeId: number, day: number) => {
        const current = grid[employeeId]?.[day];
        const next = current
            ? CYCLE[
                  (CYCLE.indexOf(current as (typeof CYCLE)[number]) + 1) %
                      CYCLE.length
              ]
            : CYCLE[0];

        setGrid((prev) => ({
            ...prev,
            [employeeId]: { ...prev[employeeId], [day]: next },
        }));

        const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        router.post(
            update().url,
            { employee_id: employeeId, date, status: next },
            { preserveScroll: true, preserveState: true },
        );
    };

    const total = (employeeId: number) =>
        Object.values(grid[employeeId] ?? {}).reduce((sum, status) => {
            if (status === 'present' || status === 'sunday') return sum + 1;
            return sum;
        }, 0);

    return (
        <>
            <Head title="Attendance" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Attendance"
                        description="Click a day cell to cycle Present / Absent / Holiday / Leave / Sunday"
                    />
                    {clientId && (
                        <Button asChild variant="outline">
                            <a
                                href={`${sheet().url}?${new URLSearchParams({ client_id: String(clientId), month: String(month), year: String(year) }).toString()}`}
                            >
                                <Download /> Download Attendance Sheet
                            </a>
                        </Button>
                    )}
                </div>

                <FilterBar
                    url={index().url}
                    clients={clients}
                    clientId={clientId}
                    month={month}
                    year={year}
                />

                <div className="text-muted-foreground flex gap-3 text-xs">
                    {CYCLE.map((s) => (
                        <span key={s} className="flex items-center gap-1">
                            <span
                                className={cn(
                                    'flex size-4 items-center justify-center rounded text-[10px] font-semibold',
                                    CELL_STYLE[s],
                                )}
                            >
                                {CELL_LABEL[s]}
                            </span>
                            {s.replace('_', ' ')}
                        </span>
                    ))}
                </div>

                <Card>
                    <CardContent className="px-0">
                        <div className="overflow-x-auto">
                            <table className="text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="bg-card sticky left-0 z-10 px-4 py-2 font-medium">
                                            Employee
                                        </th>
                                        {days.map((d) => (
                                            <th
                                                key={d}
                                                className="w-8 px-1 py-2 text-center font-medium"
                                            >
                                                {d}
                                            </th>
                                        ))}
                                        <th className="px-4 py-2 text-right font-medium">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {employees.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="bg-card sticky left-0 z-10 px-4 py-1 whitespace-nowrap">
                                                {employee.name}
                                                {employee.employee_code && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        (
                                                        {employee.employee_code}
                                                        )
                                                    </span>
                                                )}
                                            </td>
                                            {days.map((d) => {
                                                const status =
                                                    grid[employee.id]?.[d];
                                                return (
                                                    <td
                                                        key={d}
                                                        className="px-1 py-1 text-center"
                                                    >
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                cycle(
                                                                    employee.id,
                                                                    d,
                                                                )
                                                            }
                                                            className={cn(
                                                                'flex size-6 items-center justify-center rounded text-[10px] font-semibold transition-colors',
                                                                status
                                                                    ? CELL_STYLE[
                                                                          status
                                                                      ]
                                                                    : 'bg-muted hover:bg-muted-foreground/20',
                                                            )}
                                                        >
                                                            {status
                                                                ? CELL_LABEL[
                                                                      status
                                                                  ]
                                                                : ''}
                                                        </button>
                                                    </td>
                                                );
                                            })}
                                            <td className="px-4 py-1 text-right font-medium">
                                                {total(employee.id)}
                                            </td>
                                        </tr>
                                    ))}
                                    {employees.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={daysInMonth + 2}
                                                className="text-muted-foreground px-4 py-6 text-center"
                                            >
                                                No active employees for this
                                                client.
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

AttendanceIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Attendance', href: index() },
    ],
};
