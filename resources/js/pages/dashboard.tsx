import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CalendarCheck,
    CalendarDays,
    CircleAlert,
    CircleCheck,
    Clock,
    FileText,
    IndianRupee,
    Receipt,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { MONTHS } from '@/components/filter-bar';
import { StatusBadge } from '@/components/status-badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as attendanceIndex } from '@/routes/attendance';
import { edit as clientEdit, index as clientsIndex } from '@/routes/clients';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as wagesIndex } from '@/routes/wages';

type Stats = {
    active_clients: number;
    employees: number;
    invoices_due: number;
    total_billed_this_month: number;
};

type Outstanding = {
    count: number;
    amount: number;
    overdue: number;
    drafts: number;
};

type Today = {
    date: string;
    expected: number;
    present: number;
    absent: number;
    leave: number;
    off: number;
    not_marked: number;
    percent: number;
};

type Contributions = {
    wages_saved: number;
    active_employees: number;
    epf_employee: number;
    esic_employee: number;
    net_payable: number;
    gst: number;
};

type Recent = {
    type: 'invoice' | 'employee' | 'wages';
    title: string;
    detail: string;
    amount?: number;
    at: string;
    url: string;
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
    outstanding: Outstanding;
    today: Today;
    contributions: Contributions;
    recent: Recent[];
    clients: ClientRow[];
    month: number;
    year: number;
};

const rupees = (value: number) =>
    `₹${value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const TINT = {
    blue: 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300',
    green: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300',
    red: 'bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-300',
    purple: 'bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-300',
} as const;

function Panel({
    icon,
    title,
    subtitle,
    action,
    className,
    children,
}: {
    icon: ReactNode;
    title: string;
    subtitle?: string;
    action?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'bg-card min-w-0 rounded-[13px] border p-4 shadow-xs',
                className,
            )}
        >
            <div className="mb-4 flex items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span
                        className={cn(
                            'grid size-[34px] shrink-0 place-items-center rounded-[10px]',
                            TINT.blue,
                        )}
                    >
                        {icon}
                    </span>
                    <div>
                        <h2 className="text-base leading-tight font-bold">
                            {title}
                        </h2>
                        {subtitle && (
                            <p className="text-muted-foreground mt-0.5 hidden text-[11px] sm:block">
                                {subtitle}
                            </p>
                        )}
                    </div>
                </div>
                {action}
            </div>
            {children}
        </section>
    );
}

function PanelLink({ href, children }: { href: string; children: ReactNode }) {
    return (
        <Link
            href={href}
            className="text-primary hover:bg-accent focus-visible:ring-ring inline-flex items-center gap-1 rounded-md border px-2.5 py-1.5 text-[11px] font-bold whitespace-nowrap focus-visible:ring-2 focus-visible:outline-none"
        >
            {children}
            <ArrowRight className="size-3.5" />
        </Link>
    );
}

function Kpi({
    icon,
    tint,
    label,
    value,
    suffix,
    foot,
    chip,
    chipTone = 'neutral',
    progress,
}: {
    icon: ReactNode;
    tint: keyof typeof TINT;
    label: string;
    value: string;
    suffix?: string;
    foot: string;
    chip?: string;
    chipTone?: 'neutral' | 'good' | 'bad';
    progress?: number;
}) {
    return (
        <article className="bg-card min-h-[150px] rounded-[13px] border p-[18px] shadow-xs">
            <div className="mb-4 flex items-center justify-between">
                <span
                    className={cn(
                        'grid size-[38px] place-items-center rounded-[11px]',
                        TINT[tint],
                    )}
                >
                    {icon}
                </span>
                {chip && (
                    <span
                        className={cn(
                            'rounded-md px-2 py-1 text-[10px] font-medium',
                            chipTone === 'good' &&
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                            chipTone === 'bad' &&
                                'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                            chipTone === 'neutral' &&
                                'bg-muted text-muted-foreground',
                        )}
                    >
                        {chip}
                    </span>
                )}
            </div>
            <div className="text-muted-foreground text-[10px] font-extrabold tracking-wider uppercase">
                {label}
            </div>
            <div className="text-primary mt-1 text-[30px] leading-tight font-extrabold tracking-tight whitespace-nowrap">
                {value}
                {suffix && (
                    <small className="text-muted-foreground ml-1 text-[17px] font-semibold">
                        {suffix}
                    </small>
                )}
            </div>
            {progress !== undefined && (
                <div className="bg-muted my-2 h-[5px] overflow-hidden rounded-full">
                    <span
                        className="bg-primary block h-full rounded-full"
                        style={{ width: `${progress}%` }}
                    />
                </div>
            )}
            <div className="text-muted-foreground mt-1 text-[11px]">{foot}</div>
        </article>
    );
}

function Metric({
    label,
    value,
    tone,
    icon,
}: {
    label: string;
    value: number;
    tone: 'green' | 'red' | 'amber' | 'blue';
    icon: ReactNode;
}) {
    return (
        <div
            className={cn(
                'flex items-center gap-2 rounded-lg p-2.5',
                TINT[tone],
            )}
        >
            {icon}
            <div className="min-w-0">
                <small className="text-foreground/70 block text-[10px] whitespace-nowrap">
                    {label}
                </small>
                <strong className="text-foreground block text-lg leading-tight">
                    {value}
                </strong>
            </div>
        </div>
    );
}

const RECENT_ICON = {
    invoice: { icon: Receipt, tone: 'amber' },
    employee: { icon: Users, tone: 'blue' },
    wages: { icon: Wallet, tone: 'green' },
} as const;

const formatWhen = (iso: string) =>
    new Date(iso).toLocaleString('en-IN', {
        day: 'numeric',
        month: 'short',
        hour: 'numeric',
        minute: '2-digit',
        timeZone: 'Asia/Kolkata',
    });

export default function Dashboard({
    stats,
    outstanding,
    today,
    contributions,
    recent,
    clients,
    month,
    year,
}: Props) {
    const years = Array.from(
        { length: 6 },
        (_, i) => new Date().getFullYear() - 3 + i,
    );
    const changePeriod = (params: { month?: string; year?: string }) => {
        router.get(
            dashboard().url,
            { month: params.month ?? month, year: params.year ?? year },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const [ty, tm, td] = today.date.split('-').map(Number);
    const todayLabel = new Date(ty, tm - 1, td).toLocaleDateString('en-IN', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
    const marked = today.present + today.absent + today.leave + today.off;
    const sitesWithGuards = clients.filter((c) => c.employees_count > 0).length;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-5 p-3.5 sm:p-5 lg:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div className="text-muted-foreground mb-1.5 text-[10px] font-extrabold tracking-[1.6px]">
                            MAGNOVA CONSULTING PVT. LTD.
                        </div>
                        <h1 className="text-2xl font-extrabold tracking-tight sm:text-[27px]">
                            Security Operations Dashboard
                        </h1>
                        <p className="text-muted-foreground mt-1.5 text-sm">
                            Manage client sites, guard deployment, attendance
                            and monthly billing.
                        </p>
                    </div>
                    <div
                        className="flex items-center gap-2"
                        role="group"
                        aria-label="Reporting period"
                    >
                        <CalendarDays className="text-muted-foreground size-4" />
                        <Select
                            value={String(month)}
                            onValueChange={(value) =>
                                changePeriod({ month: value })
                            }
                        >
                            <SelectTrigger className="w-36" aria-label="Month">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {MONTHS.map((name, index) => (
                                    <SelectItem
                                        key={name}
                                        value={String(index + 1)}
                                    >
                                        {name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={String(year)}
                            onValueChange={(value) =>
                                changePeriod({ year: value })
                            }
                        >
                            <SelectTrigger className="w-24" aria-label="Year">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {years.map((value) => (
                                    <SelectItem
                                        key={value}
                                        value={String(value)}
                                    >
                                        {value}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3.5 min-[480px]:grid-cols-2 xl:grid-cols-4">
                    <Kpi
                        icon={<Building2 className="size-5" />}
                        tint="blue"
                        label="Active client sites"
                        value={String(stats.active_clients)}
                        foot={`${sitesWithGuards} with guards deployed`}
                    />
                    <Kpi
                        icon={<ShieldCheck className="size-5" />}
                        tint="green"
                        label="Deployed guards"
                        value={String(stats.employees)}
                        foot="Active employees on the payroll"
                    />
                    <Kpi
                        icon={<Users className="size-5" />}
                        tint="blue"
                        label="On duty today"
                        value={String(today.present)}
                        suffix={`/ ${today.expected}`}
                        chip={marked === 0 ? 'Not marked yet' : 'From register'}
                        progress={today.percent}
                        foot={
                            marked === 0
                                ? 'No attendance marked for today'
                                : `${today.percent}% present today`
                        }
                    />
                    <Kpi
                        icon={<IndianRupee className="size-5" />}
                        tint={outstanding.overdue > 0 ? 'red' : 'amber'}
                        label="Outstanding invoices"
                        value={rupees(outstanding.amount)}
                        chip={
                            outstanding.overdue > 0
                                ? `${outstanding.overdue} overdue`
                                : undefined
                        }
                        chipTone="bad"
                        foot={`${outstanding.count} sent, unpaid · ${outstanding.drafts} draft${outstanding.drafts === 1 ? '' : 's'}`}
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.35fr_1fr]">
                    <Panel
                        icon={<Building2 className="size-5" />}
                        title="Client Sites"
                        subtitle={`Work progress for ${MONTHS[month - 1]} ${year}`}
                        action={
                            <PanelLink href={clientsIndex().url}>
                                View all sites
                            </PanelLink>
                        }
                    >
                        <div className="overflow-x-auto">
                            <table className="w-full text-xs">
                                <thead>
                                    <tr className="bg-muted/50 text-muted-foreground border-y text-left">
                                        <th className="px-3 py-2.5 font-bold">
                                            Site / Client
                                        </th>
                                        <th className="px-3 py-2.5 text-center font-bold">
                                            Guards
                                        </th>
                                        <th className="px-3 py-2.5 text-center font-bold">
                                            Attendance
                                        </th>
                                        <th className="px-3 py-2.5 text-center font-bold">
                                            Wages
                                        </th>
                                        <th className="px-3 py-2.5 text-center font-bold">
                                            Invoice
                                        </th>
                                        <th className="px-3 py-2.5 text-center font-bold">
                                            Payslips
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {clients.map((client) => (
                                        <tr
                                            key={client.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-3 py-3 align-top">
                                                <Link
                                                    href={clientEdit(client.id)}
                                                    className="hover:text-primary text-[13px] font-bold"
                                                >
                                                    {client.name}
                                                </Link>
                                                {client.unit_label && (
                                                    <small className="text-muted-foreground mt-1 block text-[11px]">
                                                        {client.unit_label}
                                                    </small>
                                                )}
                                            </td>
                                            <td className="px-3 py-3 text-center align-top font-bold">
                                                {client.employees_count}
                                            </td>
                                            <td className="px-3 py-3 text-center align-top">
                                                <div className="text-muted-foreground mb-1">
                                                    {client.attendance_done}/
                                                    {client.attendance_total}{' '}
                                                    days
                                                </div>
                                                <StatusBadge
                                                    status={
                                                        client.attendance_status
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3 text-center align-top">
                                                <div className="text-muted-foreground mb-1">
                                                    {client.wages_done}/
                                                    {client.wages_total}
                                                </div>
                                                <StatusBadge
                                                    status={client.wages_status}
                                                />
                                            </td>
                                            <td className="px-3 py-3 text-center align-top">
                                                <div className="mb-1 font-bold">
                                                    {client.invoice_count}
                                                </div>
                                                <StatusBadge
                                                    status={
                                                        client.invoice_status
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3 text-center align-top">
                                                <div className="text-muted-foreground mb-1">
                                                    {client.payslip_done}/
                                                    {client.payslip_total}
                                                </div>
                                                <StatusBadge
                                                    status={
                                                        client.payslip_status
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                    {clients.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="text-muted-foreground py-8 text-center"
                                            >
                                                No active clients yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div className="text-muted-foreground flex justify-between px-1 pt-3 text-[11px]">
                            <span>
                                {clients.length} active site
                                {clients.length === 1 ? '' : 's'}
                            </span>
                            <Link
                                href={clientsIndex().url}
                                className="text-primary font-bold hover:underline"
                            >
                                Manage clients
                            </Link>
                        </div>
                    </Panel>

                    <Panel
                        icon={<CalendarCheck className="size-5" />}
                        title="Attendance Today"
                        subtitle={todayLabel}
                        action={
                            <PanelLink href={attendanceIndex().url}>
                                View attendance
                            </PanelLink>
                        }
                    >
                        <p className="text-muted-foreground mb-3 text-xs sm:hidden">
                            {todayLabel}
                        </p>
                        <div className="flex items-center justify-between px-1">
                            <div className="flex items-baseline gap-2.5">
                                <div className="text-primary text-4xl font-extrabold tracking-tight">
                                    {today.percent}
                                    <small className="text-xl">%</small>
                                </div>
                                <span className="text-muted-foreground text-xs">
                                    Present of active guards
                                </span>
                            </div>
                            <div className="text-right">
                                <strong className="block text-xl">
                                    {today.present}{' '}
                                    <small className="text-muted-foreground text-[13px]">
                                        / {today.expected}
                                    </small>
                                </strong>
                                <span className="text-muted-foreground text-xs">
                                    Present today
                                </span>
                            </div>
                        </div>
                        <div
                            className="bg-muted my-3 h-2.5 overflow-hidden rounded-full"
                            role="progressbar"
                            aria-label="Attendance today"
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={today.percent}
                            aria-valuetext={`${today.present} of ${today.expected} present, ${today.percent} percent`}
                        >
                            <span
                                className="bg-primary block h-full rounded-full"
                                style={{ width: `${today.percent}%` }}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <Metric
                                label="Present"
                                value={today.present}
                                tone="green"
                                icon={<CircleCheck className="size-5" />}
                            />
                            <Metric
                                label="Absent"
                                value={today.absent}
                                tone="red"
                                icon={<CircleAlert className="size-5" />}
                            />
                            <Metric
                                label="On leave"
                                value={today.leave}
                                tone="amber"
                                icon={<CalendarDays className="size-5" />}
                            />
                            <Metric
                                label="Off / holiday"
                                value={today.off}
                                tone="blue"
                                icon={<Clock className="size-5" />}
                            />
                        </div>
                        <p className="text-muted-foreground mt-3 text-xs">
                            {today.not_marked > 0
                                ? `${today.not_marked} guard${today.not_marked === 1 ? '' : 's'} not marked yet today.`
                                : 'Everyone is marked for today.'}
                        </p>
                        <Link
                            href={attendanceIndex().url}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-ring mt-4 flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-xs font-bold focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <CalendarCheck className="size-4" />
                            Open attendance register
                        </Link>
                    </Panel>

                    <Panel
                        icon={<Clock className="size-5" />}
                        title="Recent Records"
                        subtitle="Latest invoices, employees and saved wages"
                        action={
                            <PanelLink href={invoicesIndex().url}>
                                All invoices
                            </PanelLink>
                        }
                    >
                        {recent.length === 0 ? (
                            <p className="text-muted-foreground py-6 text-center text-sm">
                                Nothing recorded yet.
                            </p>
                        ) : (
                            <ul>
                                {recent.map((item) => {
                                    const { icon: Icon, tone } =
                                        RECENT_ICON[item.type];

                                    return (
                                        <li
                                            key={`${item.type}-${item.at}-${item.title}`}
                                            className="border-t first:border-0"
                                        >
                                            <Link
                                                href={item.url}
                                                className="hover:bg-muted/50 focus-visible:ring-ring flex items-start gap-3 rounded-md px-1 py-3 focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                <span
                                                    className={cn(
                                                        'grid size-[30px] shrink-0 place-items-center rounded-full',
                                                        TINT[tone],
                                                    )}
                                                >
                                                    <Icon className="size-4" />
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <strong className="block text-xs">
                                                        {item.title}
                                                    </strong>
                                                    <span className="text-muted-foreground mt-1 block text-[11px]">
                                                        {[
                                                            item.detail,
                                                            item.amount !==
                                                                undefined &&
                                                                rupees(
                                                                    item.amount,
                                                                ),
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </span>
                                                </span>
                                                <time
                                                    dateTime={item.at}
                                                    className="text-muted-foreground pt-0.5 text-[10px] whitespace-nowrap"
                                                >
                                                    {formatWhen(item.at)}
                                                </time>
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </Panel>

                    <Panel
                        icon={<FileText className="size-5" />}
                        title="Payroll & Billing Summary"
                        subtitle={`Saved totals for ${MONTHS[month - 1]} ${year}`}
                        action={
                            <PanelLink
                                href={
                                    wagesIndex({ query: { month, year } }).url
                                }
                            >
                                View payroll
                            </PanelLink>
                        }
                    >
                        <div className="grid grid-cols-1 gap-2 min-[480px]:grid-cols-3">
                            {[
                                {
                                    label: 'EPF (employee share)',
                                    value: contributions.epf_employee,
                                    tone: 'purple',
                                },
                                {
                                    label: 'ESIC (employee share)',
                                    value: contributions.esic_employee,
                                    tone: 'red',
                                },
                                {
                                    label: 'GST on invoices',
                                    value: contributions.gst,
                                    tone: 'blue',
                                },
                            ].map((card) => (
                                <div
                                    key={card.label}
                                    className="rounded-lg border p-3"
                                >
                                    <span
                                        className={cn(
                                            'mb-2 grid size-[30px] place-items-center rounded-lg',
                                            TINT[
                                                card.tone as keyof typeof TINT
                                            ],
                                        )}
                                    >
                                        <IndianRupee className="size-4" />
                                    </span>
                                    <small className="text-muted-foreground block text-[10px]">
                                        {card.label}
                                    </small>
                                    <strong className="mt-0.5 block text-sm">
                                        {rupees(card.value)}
                                    </strong>
                                </div>
                            ))}
                        </div>
                        <Link
                            href={invoicesIndex().url}
                            className={cn(
                                'mt-3 flex items-center gap-3 rounded-lg border p-3 text-xs',
                                outstanding.overdue > 0
                                    ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200',
                            )}
                        >
                            {outstanding.overdue > 0 ? (
                                <CircleAlert className="size-5 shrink-0" />
                            ) : (
                                <CircleCheck className="size-5 shrink-0" />
                            )}
                            <span className="flex-1">
                                <strong className="block">
                                    {outstanding.overdue > 0
                                        ? `${outstanding.overdue} invoice${outstanding.overdue === 1 ? '' : 's'} overdue`
                                        : 'No overdue invoices'}
                                </strong>
                                <small className="mt-0.5 block text-[11px]">
                                    Outstanding: {rupees(outstanding.amount)}
                                </small>
                            </span>
                            <ArrowRight className="size-4" />
                        </Link>
                        <Link
                            href={wagesIndex({ query: { month, year } }).url}
                            className={cn(
                                'mt-2 flex items-center gap-3 rounded-lg border p-3 text-xs',
                                contributions.wages_saved >=
                                    contributions.active_employees &&
                                    contributions.active_employees > 0
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200'
                                    : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200',
                            )}
                        >
                            <Wallet className="size-5 shrink-0" />
                            <span className="flex-1">
                                <strong className="block">
                                    Wages saved for {contributions.wages_saved}{' '}
                                    of {contributions.active_employees}{' '}
                                    employees
                                </strong>
                                <small className="mt-0.5 block text-[11px]">
                                    Net payable{' '}
                                    {rupees(contributions.net_payable)}
                                </small>
                            </span>
                            <ArrowRight className="size-4" />
                        </Link>
                        <p className="text-muted-foreground mt-3 text-[11px]">
                            Totals come from saved wages and generated invoices.
                            The app does not track whether EPF, ESIC or GST
                            returns have been filed.
                        </p>
                    </Panel>
                </div>
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
