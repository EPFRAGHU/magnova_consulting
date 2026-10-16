import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar } from '@/components/filter-bar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index, save, sheet } from '@/routes/attendance';

type ClientOption = { id: number; name: string; unit_label: string | null };
type Employee = {
    id: number;
    name: string;
    employee_code: string | null;
    designation: string | null;
};

type AttendanceMap = Record<number, Record<number, string>>;

const CYCLE = ['present', 'absent', 'holiday', 'leave', 'sunday', 'off'] as const;

const CELL_STYLE: Record<string, string> = {
    present:
        'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    sunday: 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    absent: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
    holiday:
        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    leave: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
    off: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
};

const CELL_LABEL: Record<string, string> = {
    present: 'P',
    absent: 'A',
    holiday: 'H',
    leave: 'L',
    sunday: 'S',
    off: 'OFF',
};

const WEEKDAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

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
    const [dirty, setDirty] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        setGrid(attendance ?? {});
        setDirty(false);
    }, [attendance, clientId, month, year]);

    const days = useMemo(
        () =>
            Array.from({ length: daysInMonth }, (_, i) => {
                const day = i + 1;
                const weekday = new Date(year, month - 1, day).getDay();

                return {
                    day,
                    weekday,
                    weekdayLabel: WEEKDAY_LABELS[weekday],
                    isSunday: weekday === 0,
                    isWeekStart: weekday === 0 && day !== 1,
                    weekIndex: Math.floor(
                        (day - 1 + new Date(year, month - 1, 1).getDay()) /
                            7,
                    ),
                };
            }),
        [daysInMonth, month, year],
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
        setDirty(true);
    };

    const saveAttendance = () => {
        setProcessing(true);
        router.post(
            save().url,
            {
                month,
                year,
                records: Object.entries(grid).flatMap(([employeeId, days]) =>
                    Object.entries(days).map(([day, status]) => ({
                        employee_id: Number(employeeId),
                        date: `${year}-${String(month).padStart(2, '0')}-${String(Number(day)).padStart(2, '0')}`,
                        status,
                    })),
                ),
            },
            {
                preserveScroll: true,
                onSuccess: () => setDirty(false),
                onFinish: () => setProcessing(false),
            },
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
                <div className="flex items-center justify-between gap-2">
                    <Heading
                        title="Attendance"
                        description="Click a day cell to cycle Present / Absent / Holiday / Leave / Sunday / OFF, then save"
                    />
                    <div className="flex gap-2">
                        {clientId && (
                            <Button asChild variant="outline">
                                <a
                                    href={`${sheet().url}?${new URLSearchParams({ client_id: String(clientId), month: String(month), year: String(year) }).toString()}`}
                                >
                                    <Download /> Download Attendance Sheet
                                </a>
                            </Button>
                        )}
                        <Button
                            onClick={saveAttendance}
                            disabled={processing || !dirty || employees.length === 0}
                        >
                            {processing ? 'Saving...' : 'Save Attendance'}
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

                <div className="text-muted-foreground flex flex-wrap gap-x-3 gap-y-1 text-xs">
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
                        <div className="w-full overflow-hidden">
                            <table className="w-full table-fixed text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th
                                            rowSpan={2}
                                            className="bg-card sticky left-0 z-10 w-32 px-2 py-2 align-bottom text-xs font-medium sm:w-44 sm:px-4"
                                        >
                                            Employee
                                        </th>
                                        <th
                                            rowSpan={2}
                                            className="w-20 px-1 py-2 align-bottom text-xs font-medium sm:w-28 sm:px-2"
                                        >
                                            Designation
                                        </th>
                                        {days.map((date) => (
                                            <th
                                                key={date.day}
                                                className={cn(
                                                    'px-0 pt-2 pb-1 text-center text-[9px] font-semibold uppercase sm:text-[10px]',
                                                    date.isSunday &&
                                                        'bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-300',
                                                    date.isWeekStart &&
                                                        'border-l-2 border-slate-300 dark:border-slate-600',
                                                )}
                                            >
                                                {date.weekdayLabel}
                                            </th>
                                        ))}
                                        <th
                                            rowSpan={2}
                                            className="w-10 px-1 py-2 text-right align-bottom text-xs font-medium sm:w-16 sm:px-4"
                                        >
                                            Total
                                        </th>
                                    </tr>
                                    <tr className="text-muted-foreground border-b text-left">
                                        {days.map((date) => (
                                            <th
                                                key={date.day}
                                                className={cn(
                                                    'px-0 py-1 text-center text-xs font-medium sm:text-sm',
                                                    date.isSunday &&
                                                        'bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-300',
                                                    date.isWeekStart &&
                                                        'border-l-2 border-slate-300 dark:border-slate-600',
                                                )}
                                            >
                                                {date.day}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {employees.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="bg-card sticky left-0 z-10 max-w-0 overflow-hidden px-2 py-2 align-top sm:px-4">
                                                <div className="min-w-0 max-w-full overflow-hidden">
                                                    <span className="block break-words text-xs leading-tight font-medium sm:text-sm">
                                                        {employee.name}
                                                    </span>
                                                    {employee.employee_code && (
                                                        <span className="text-muted-foreground mt-0.5 block break-all text-[10px] leading-tight sm:text-xs">
                                                            {employee.employee_code}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-1 py-2 align-top text-xs leading-tight break-words sm:px-2">
                                                {employee.designation ?? '-'}
                                            </td>
                                            {days.map((date) => {
                                                const status =
                                                    grid[employee.id]?.[date.day];
                                                return (
                                                    <td
                                                        key={date.day}
                                                        className={cn(
                                                            'px-0 py-1 text-center',
                                                            date.weekIndex % 2 === 1 &&
                                                                'bg-slate-50/70 dark:bg-slate-900/30',
                                                            date.isSunday &&
                                                                'bg-red-50/60 dark:bg-red-950/20',
                                                            date.isWeekStart &&
                                                                'border-l-2 border-slate-300 dark:border-slate-600',
                                                        )}
                                                    >
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                cycle(
                                                                    employee.id,
                                                                    date.day,
                                                                )
                                                            }
                                                            className={cn(
                                                                'mx-auto flex size-5 items-center justify-center rounded text-[9px] font-semibold transition-colors sm:size-6 sm:text-[10px]',
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
                                            <td className="px-1 py-1 text-right text-xs font-medium sm:px-4 sm:text-sm">
                                                {total(employee.id)}
                                            </td>
                                        </tr>
                                    ))}
                                    {employees.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={daysInMonth + 3}
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
