import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar, MONTHS } from '@/components/filter-bar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index } from '@/routes/attendance';
import { register, save } from '@/routes/attendance/contract';

type ClientOption = { id: number; name: string; unit_label: string | null };

type ClientInfo = {
    id: number;
    name: string;
    principal_employer_name: string | null;
    principal_employer_address: string | null;
    contract_start: string | null;
    contract_end: string | null;
    register_contractor_address: string | null;
};

type Employee = {
    id: number;
    name: string;
    employee_code: string | null;
    category: string;
};

type Cell = { status: string | null; ot: number };
type Grid = Record<number, Record<number, Cell>>;
type Days = Record<number, Record<number, { status: string; ot: number }>>;

// Blank means a weekly off or no entry, as in the client's register.
const CYCLE: (string | null)[] = [
    null,
    'present',
    'absent',
    'half_day',
    'holiday',
];
const CODE: Record<string, string> = {
    present: 'P',
    absent: 'A',
    half_day: 'HD',
    holiday: 'H',
    off: '',
};
const KEYS: Record<string, string | null> = {
    p: 'present',
    a: 'absent',
    d: 'half_day',
    h: 'holiday',
    x: null,
    Backspace: null,
    Delete: null,
};
const STYLE: Record<string, string> = {
    present:
        'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    absent: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
    half_day:
        'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
    holiday: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
};
const WEEKDAYS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

const toDate = (value: string | null) =>
    value ? new Date(value).toLocaleDateString('en-IN') : '-';
const clean = (status: string | null) => (status === 'off' ? null : status);

function buildGrid(employees: Employee[], days: Days): Grid {
    const grid: Grid = {};
    for (const employee of employees) {
        grid[employee.id] = {};
        for (const [day, cell] of Object.entries(days?.[employee.id] ?? {})) {
            grid[employee.id][Number(day)] = {
                status: clean(cell.status),
                ot: Number(cell.ot),
            };
        }
    }

    return grid;
}

export default function ContractAttendance({
    client,
    employees,
    days,
    clients,
    client_id: clientId,
    month,
    year,
    days_in_month: daysInMonth,
}: {
    client: ClientInfo;
    employees: Employee[];
    days: Days;
    clients: ClientOption[];
    client_id: number;
    month: number;
    year: number;
    days_in_month: number;
}) {
    const [grid, setGrid] = useState<Grid>(() => buildGrid(employees, days));
    const [changed, setChanged] = useState<Set<string>>(new Set());
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        setGrid(buildGrid(employees, days));
        setChanged(new Set());
    }, [employees, days, clientId, month, year]);

    const columns = useMemo(
        () =>
            Array.from({ length: daysInMonth }, (_, i) => {
                const day = i + 1;
                const weekday = new Date(year, month - 1, day).getDay();

                return {
                    day,
                    weekday: WEEKDAYS[weekday],
                    sunday: weekday === 0,
                };
            }),
        [daysInMonth, month, year],
    );

    const cellOf = (employeeId: number, day: number): Cell =>
        grid[employeeId]?.[day] ?? { status: null, ot: 0 };

    // Always works from the latest state, so quick repeated clicks never skip a step.
    const update = (
        employeeId: number,
        day: number,
        patch: Partial<Cell> | ((cell: Cell) => Partial<Cell>),
    ) => {
        setGrid((prev) => {
            const current = prev[employeeId]?.[day] ?? { status: null, ot: 0 };
            const change = typeof patch === 'function' ? patch(current) : patch;

            return {
                ...prev,
                [employeeId]: {
                    ...prev[employeeId],
                    [day]: { ...current, ...change },
                },
            };
        });
        setChanged((prev) => new Set(prev).add(`${employeeId}-${day}`));
    };

    const cycle = (employeeId: number, day: number) =>
        update(employeeId, day, (cell) => ({
            status: CYCLE[(CYCLE.indexOf(cell.status) + 1) % CYCLE.length],
        }));

    const fillWorkingDays = () => {
        for (const employee of employees) {
            for (const column of columns) {
                if (!column.sunday && !cellOf(employee.id, column.day).status) {
                    update(employee.id, column.day, { status: 'present' });
                }
            }
        }
    };

    const totals = (employeeId: number) => {
        const cells = Object.values(grid[employeeId] ?? {});
        const present =
            cells.filter((c) => c.status === 'present').length +
            0.5 * cells.filter((c) => c.status === 'half_day').length;
        const holidays = cells.filter((c) => c.status === 'holiday').length;
        const ot = cells.reduce((sum, c) => sum + (c.ot || 0), 0);

        return { present, holidays, total: present + holidays, ot };
    };

    const saveAttendance = () => {
        setProcessing(true);
        router.post(
            save().url,
            {
                month,
                year,
                records: [...changed].map((key) => {
                    const [employeeId, day] = key.split('-').map(Number);
                    const cell = cellOf(employeeId, day);

                    return {
                        employee_id: employeeId,
                        date: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
                        status: cell.status,
                        ot_hours: cell.ot || 0,
                    };
                }),
            },
            {
                preserveScroll: true,
                onSuccess: () => setChanged(new Set()),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const number = (value: number) =>
        Number.isInteger(value) ? String(value) : value.toFixed(1);

    return (
        <>
            <Head title="Attendance" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Register of Attendance"
                        description="Click a day to cycle P (present), A (absent), HD (half day), H (holiday) or blank (weekly off). Or select a cell and press P, A, D, H or X. Enter overtime hours under each day."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <a
                                href={`${register().url}?${new URLSearchParams({ client_id: String(clientId), month: String(month), year: String(year) }).toString()}`}
                            >
                                <Download /> Download Register of Attendance
                            </a>
                        </Button>
                        <Button
                            variant="outline"
                            onClick={fillWorkingDays}
                            disabled={employees.length === 0}
                        >
                            Mark all present except Sundays
                        </Button>
                        <Button
                            onClick={saveAttendance}
                            disabled={processing || changed.size === 0}
                        >
                            {processing
                                ? 'Saving...'
                                : `Save Attendance${changed.size ? ` (${changed.size})` : ''}`}
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

                <div className="bg-card rounded-md border p-3 text-sm">
                    <div className="font-medium">
                        Contractor: {client.register_contractor_address ?? '-'}
                    </div>
                    <div>
                        Principal employer:{' '}
                        {client.principal_employer_name ?? '-'}
                        {client.principal_employer_address
                            ? `, ${client.principal_employer_address}`
                            : ''}
                    </div>
                    <div className="text-muted-foreground">
                        Contract period: {toDate(client.contract_start)} to{' '}
                        {toDate(client.contract_end)} · Month:{' '}
                        {MONTHS[month - 1]}, {year}
                    </div>
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground">
                                <th className="bg-card sticky left-0 z-10 w-44 min-w-44 px-2 py-2 text-left font-medium">
                                    Name
                                </th>
                                {columns.map((column) => (
                                    <th
                                        key={column.day}
                                        className={cn(
                                            'min-w-[26px] px-0 py-1 text-center font-medium',
                                            column.sunday &&
                                                'bg-slate-200/70 dark:bg-slate-700/50',
                                        )}
                                    >
                                        <div>{column.day}</div>
                                        <div className="text-[10px] font-normal">
                                            {column.weekday}
                                        </div>
                                    </th>
                                ))}
                                <th className="px-1.5 py-2 text-center font-medium">
                                    Present days
                                </th>
                                <th className="px-1.5 py-2 text-center font-medium">
                                    Holidays
                                </th>
                                <th className="px-1.5 py-2 text-center font-medium">
                                    Total days
                                </th>
                                <th className="px-1.5 py-2 text-center font-medium">
                                    OT hours
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {employees.map((employee, index) => {
                                const t = totals(employee.id);

                                return (
                                    <FragmentRows key={employee.id}>
                                        <tr className="border-t">
                                            <td
                                                rowSpan={2}
                                                className="bg-card sticky left-0 z-10 px-2 py-1 align-top"
                                            >
                                                <div className="font-medium">
                                                    {index + 1}. {employee.name}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {employee.category}
                                                </div>
                                            </td>
                                            {columns.map((column) => {
                                                const cell = cellOf(
                                                    employee.id,
                                                    column.day,
                                                );

                                                return (
                                                    <td
                                                        key={column.day}
                                                        className={cn(
                                                            'p-0 text-center',
                                                            column.sunday &&
                                                                'bg-slate-100/80 dark:bg-slate-800/40',
                                                        )}
                                                    >
                                                        <button
                                                            type="button"
                                                            aria-label={`${employee.name}, day ${column.day}: ${cell.status ?? 'blank'}`}
                                                            onClick={() =>
                                                                cycle(
                                                                    employee.id,
                                                                    column.day,
                                                                )
                                                            }
                                                            onKeyDown={(e) => {
                                                                const key =
                                                                    e.key
                                                                        .length ===
                                                                    1
                                                                        ? e.key.toLowerCase()
                                                                        : e.key;
                                                                if (
                                                                    key in KEYS
                                                                ) {
                                                                    e.preventDefault();
                                                                    update(
                                                                        employee.id,
                                                                        column.day,
                                                                        {
                                                                            status: KEYS[
                                                                                key
                                                                            ],
                                                                        },
                                                                    );
                                                                }
                                                            }}
                                                            className={cn(
                                                                'focus-visible:ring-ring h-7 w-full min-w-[26px] text-[11px] font-semibold focus-visible:ring-2 focus-visible:outline-none',
                                                                cell.status &&
                                                                    STYLE[
                                                                        cell
                                                                            .status
                                                                    ],
                                                            )}
                                                        >
                                                            {cell.status
                                                                ? CODE[
                                                                      cell
                                                                          .status
                                                                  ]
                                                                : ''}
                                                        </button>
                                                    </td>
                                                );
                                            })}
                                            <td className="px-2 text-center font-semibold">
                                                {number(t.present)}
                                            </td>
                                            <td className="px-2 text-center">
                                                {t.holidays}
                                            </td>
                                            <td className="px-2 text-center font-semibold">
                                                {number(t.total)}
                                            </td>
                                            <td
                                                rowSpan={2}
                                                className="px-2 text-center font-semibold"
                                            >
                                                {number(t.ot)}
                                            </td>
                                        </tr>
                                        <tr>
                                            {columns.map((column) => (
                                                <td
                                                    key={column.day}
                                                    className={cn(
                                                        'p-0',
                                                        column.sunday &&
                                                            'bg-slate-100/80 dark:bg-slate-800/40',
                                                    )}
                                                >
                                                    <input
                                                        type="number"
                                                        min={0}
                                                        max={24}
                                                        step={0.5}
                                                        inputMode="decimal"
                                                        aria-label={`${employee.name}, day ${column.day}: overtime hours`}
                                                        value={
                                                            cellOf(
                                                                employee.id,
                                                                column.day,
                                                            ).ot || ''
                                                        }
                                                        placeholder="·"
                                                        onChange={(e) =>
                                                            update(
                                                                employee.id,
                                                                column.day,
                                                                {
                                                                    ot: Math.max(
                                                                        0,
                                                                        Math.min(
                                                                            24,
                                                                            Number(
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                            ) ||
                                                                                0,
                                                                        ),
                                                                    ),
                                                                },
                                                            )
                                                        }
                                                        className="text-muted-foreground focus:bg-accent h-6 w-full min-w-[26px] [appearance:textfield] bg-transparent text-center text-[11px] outline-none [&::-webkit-inner-spin-button]:appearance-none"
                                                    />
                                                </td>
                                            ))}
                                            <td
                                                colSpan={3}
                                                className="text-muted-foreground px-2 text-center text-[10px]"
                                            >
                                                OT hours (row)
                                            </td>
                                        </tr>
                                    </FragmentRows>
                                );
                            })}
                            {employees.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={daysInMonth + 5}
                                        className="text-muted-foreground px-6 py-8 text-center"
                                    >
                                        No active employees in this unit yet.
                                        Add them under Guards &amp; Staff.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <p className="text-muted-foreground text-xs">
                    Paid days = present days + half days (0.5 each) + holidays.
                    Weekly offs and absences are not paid. Overtime is paid for
                    the hours entered.
                </p>
            </div>
        </>
    );
}

function FragmentRows({ children }: { children: React.ReactNode }) {
    return <>{children}</>;
}

ContractAttendance.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Attendance', href: index() },
    ],
};
