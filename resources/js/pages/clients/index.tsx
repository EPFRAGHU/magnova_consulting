import { Head, Link, router } from '@inertiajs/react';
import {
    Check,
    Copy,
    Filter,
    Pencil,
    Phone,
    Plus,
    RotateCcw,
    Search,
    Upload,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DeleteButton } from '@/components/delete-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { SelectNative } from '@/components/ui/select-native';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    create,
    destroy,
    edit,
    index,
    load,
    regenerateInvoices,
} from '@/routes/clients';

// Soft pastel fills with a thin border, one tone per meaning.
const TONE = {
    green: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300',
    slate: 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
    indigo: 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-300',
    amber: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-300',
    violet: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950/60 dark:text-violet-300',
    sky: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/60 dark:text-sky-300',
    rose: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-300',
    teal: 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-300',
} as const;

function Pill({
    tone,
    children,
}: {
    tone: keyof typeof TONE;
    children: React.ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded border px-2 py-0.5 text-[11px] font-medium whitespace-nowrap',
                TONE[tone],
            )}
        >
            {children}
        </span>
    );
}

type ClientRow = {
    id: number;
    name: string;
    unit_label: string | null;
    gstin: string | null;
    contact_person: string | null;
    contact_phone: string | null;
    active: boolean;
    bonus_enabled: boolean;
    service_charge_on_basic: boolean;
    gst_exempt: boolean;
    esic_unit_mode: string;
    payroll_profile: string;
    employees_count: number;
};

type RegenerateAsk = { client_id: number; name: string; count: number };

// The list as a spreadsheet: what is on screen, one row per client.
function exportCsv(clients: ClientRow[]) {
    const cell = (value: string | number | null) =>
        `"${String(value ?? '').replace(/"/g, '""')}"`;
    const rows = [
        [
            'Name',
            'Unit / Site',
            'GSTIN',
            'Contact person',
            'Contact phone',
            'Staff count',
            'Status',
        ],
        ...clients.map((c) => [
            c.name,
            c.unit_label,
            c.gstin,
            c.contact_person,
            c.contact_phone,
            c.employees_count,
            c.active ? 'Active' : 'Inactive',
        ]),
    ];
    const blob = new Blob(
        ['﻿' + rows.map((r) => r.map(cell).join(',')).join('\r\n')],
        {
            type: 'text/csv;charset=utf-8',
        },
    );
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `clients-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}

export default function ClientsIndex({
    clients,
    search,
    status,
    loaded_client_id,
    regenerate,
}: {
    clients: ClientRow[];
    search: string;
    status: string;
    loaded_client_id: number | null;
    regenerate: RegenerateAsk | null;
}) {
    const [query, setQuery] = useState(search ?? '');
    const [asking, setAsking] = useState(regenerate !== null);
    const [working, setWorking] = useState(false);
    const [copied, copy] = useClipboard();
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );

    const regenerateNow = () => {
        if (!regenerate) return;
        setWorking(true);
        router.post(
            regenerateInvoices(regenerate.client_id).url,
            {},
            {
                onFinish: () => {
                    setWorking(false);
                    setAsking(false);
                },
            },
        );
    };

    const filter = (next: { search?: string; status?: string }) =>
        router.get(
            index().url,
            {
                search: next.search ?? query,
                status: next.status ?? status,
            },
            { preserveState: true, replace: true },
        );

    const runSearch = (value: string) => {
        setQuery(value);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(() => filter({ search: value }), 250);
    };

    const reset = () => {
        setQuery('');
        router.get(index().url, {}, { preserveState: true, replace: true });
    };

    return (
        <>
            <Head title="Clients" />
            <Dialog
                open={asking && regenerate !== null}
                onOpenChange={setAsking}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Update draft invoices?</DialogTitle>
                        <DialogDescription>
                            You changed a billing setting for{' '}
                            <strong>{regenerate?.name}</strong>. It has{' '}
                            {regenerate?.count}{' '}
                            {regenerate?.count === 1
                                ? 'draft invoice'
                                : 'draft invoices'}{' '}
                            that still use the old setting. Regenerate{' '}
                            {regenerate?.count === 1 ? 'it' : 'them'} now so the
                            amounts match? Invoice numbers stay the same. Sent
                            and paid invoices are not changed.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setAsking(false)}
                            disabled={working}
                        >
                            No, leave them
                        </Button>
                        <Button
                            type="button"
                            onClick={regenerateNow}
                            disabled={working}
                        >
                            {working ? 'Regenerating...' : 'Yes, regenerate'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight">
                                Clients
                            </h1>
                            <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                {clients.length} Total
                            </span>
                        </div>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Establishments Magnova supplies manpower to
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => exportCsv(clients)}
                            disabled={clients.length === 0}
                        >
                            <Upload /> Export CSV
                        </Button>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> Add Client
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="bg-card flex flex-col items-stretch justify-between gap-3 rounded-2xl border p-3 shadow-sm sm:flex-row sm:items-center">
                    <div className="relative w-full sm:max-w-md">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2" />
                        <input
                            type="search"
                            value={query}
                            onChange={(e) => runSearch(e.target.value)}
                            placeholder="Search clients by name, GSTIN, location, or contact..."
                            aria-label="Search clients"
                            className="bg-muted/40 placeholder:text-muted-foreground focus:bg-background focus:ring-ring/40 h-10 w-full rounded-xl border pr-4 pl-10 text-sm outline-none focus:ring-2"
                        />
                    </div>
                    <div className="flex items-center justify-end gap-2">
                        <span className="text-muted-foreground flex items-center gap-1.5 text-sm">
                            <Filter className="size-3.5" /> Filter:
                        </span>
                        <SelectNative
                            value={status}
                            onChange={(e) => filter({ status: e.target.value })}
                            aria-label="Filter by status"
                            className="h-10 w-36 rounded-xl"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </SelectNative>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={reset}
                            title="Clear search and filter"
                            aria-label="Clear search and filter"
                            className="size-10 rounded-xl"
                        >
                            <RotateCcw />
                        </Button>
                    </div>
                </div>

                <div className="bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                                <tr className="border-b">
                                    <th className="py-3.5 pr-3 pl-6">
                                        Name &amp; establishment details
                                    </th>
                                    <th className="px-3 py-3.5">GSTIN</th>
                                    <th className="px-3 py-3.5">
                                        Contact person
                                    </th>
                                    <th className="px-3 py-3.5 text-center">
                                        Staff count
                                    </th>
                                    <th className="px-3 py-3.5">
                                        Status &amp; payroll rules
                                    </th>
                                    <th className="px-3 py-3.5 text-center">
                                        Load
                                    </th>
                                    <th className="py-3.5 pr-6 pl-3 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {clients.map((client) => {
                                    const isLoaded =
                                        client.id === loaded_client_id;

                                    return (
                                        <tr
                                            key={client.id}
                                            className={cn(
                                                'hover:bg-muted/40 transition-colors duration-150',
                                                isLoaded &&
                                                    'bg-emerald-50/50 dark:bg-emerald-950/20',
                                            )}
                                        >
                                            <td className="max-w-sm py-4 pr-3 pl-6">
                                                <div className="leading-snug font-semibold">
                                                    {client.name}
                                                </div>
                                                {client.unit_label && (
                                                    <div className="text-muted-foreground mt-0.5 line-clamp-2 text-xs">
                                                        {client.unit_label}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-3 py-4 whitespace-nowrap">
                                                {client.gstin ? (
                                                    <span className="bg-muted rounded-md border px-2 py-1 font-mono text-xs">
                                                        {client.gstin}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground/60 font-mono text-xs">
                                                        &mdash;
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-3 py-4">
                                                {client.contact_person && (
                                                    <div className="text-xs font-medium">
                                                        {client.contact_person}
                                                    </div>
                                                )}
                                                {client.contact_phone ? (
                                                    <div className="group text-muted-foreground mt-0.5 flex items-center gap-1 text-xs">
                                                        <Phone className="size-3" />
                                                        {client.contact_phone}
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                copy(
                                                                    client.contact_phone ??
                                                                        '',
                                                                )
                                                            }
                                                            title="Copy phone number"
                                                            aria-label={`Copy ${client.contact_phone}`}
                                                            className="hover:text-foreground rounded p-0.5 opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                                                        >
                                                            {copied ===
                                                            client.contact_phone ? (
                                                                <Check className="size-3 text-emerald-600" />
                                                            ) : (
                                                                <Copy className="size-3" />
                                                            )}
                                                        </button>
                                                    </div>
                                                ) : (
                                                    !client.contact_person && (
                                                        <span className="text-muted-foreground/60 text-xs">
                                                            &mdash;
                                                        </span>
                                                    )
                                                )}
                                            </td>
                                            <td className="px-3 py-4 text-center whitespace-nowrap">
                                                <span className="bg-muted inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold">
                                                    {client.employees_count}
                                                </span>
                                            </td>
                                            <td className="px-3 py-4">
                                                <div className="flex max-w-xs flex-wrap gap-1.5">
                                                    <Pill
                                                        tone={
                                                            client.active
                                                                ? 'green'
                                                                : 'slate'
                                                        }
                                                    >
                                                        {client.active
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </Pill>
                                                    <Pill
                                                        tone={
                                                            client.bonus_enabled
                                                                ? 'indigo'
                                                                : 'slate'
                                                        }
                                                    >
                                                        {client.bonus_enabled
                                                            ? 'Bonus on'
                                                            : 'Bonus off'}
                                                    </Pill>
                                                    <Pill
                                                        tone={
                                                            client.service_charge_on_basic
                                                                ? 'violet'
                                                                : 'amber'
                                                        }
                                                    >
                                                        {client.service_charge_on_basic
                                                            ? 'Service on Basic'
                                                            : 'Service on Gross'}
                                                    </Pill>
                                                    <Pill
                                                        tone={
                                                            client.gst_exempt
                                                                ? 'rose'
                                                                : 'sky'
                                                        }
                                                    >
                                                        {client.gst_exempt
                                                            ? 'No GST'
                                                            : 'GST on'}
                                                    </Pill>
                                                    {client.payroll_profile ===
                                                        'contract_labour' && (
                                                        <Pill tone="teal">
                                                            Contract labour
                                                        </Pill>
                                                    )}
                                                    {client.esic_unit_mode !==
                                                        'statutory' && (
                                                        <Pill
                                                            tone={
                                                                client.esic_unit_mode ===
                                                                'ceiling'
                                                                    ? 'violet'
                                                                    : 'slate'
                                                            }
                                                        >
                                                            {client.esic_unit_mode ===
                                                            'ceiling'
                                                                ? 'ESIC on ceiling'
                                                                : 'No ESIC above ceiling'}
                                                        </Pill>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-3 py-4 text-center">
                                                <button
                                                    type="button"
                                                    role="switch"
                                                    aria-checked={isLoaded}
                                                    aria-label={`Load ${client.name}`}
                                                    disabled={isLoaded}
                                                    onClick={() =>
                                                        router.post(
                                                            load(client.id).url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    className={cn(
                                                        'relative h-5 w-9 rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
                                                        isLoaded
                                                            ? 'bg-emerald-500'
                                                            : 'bg-slate-200 dark:bg-slate-600',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow transition-transform',
                                                            isLoaded &&
                                                                'translate-x-4',
                                                        )}
                                                    />
                                                </button>
                                            </td>
                                            <td className="py-4 pr-6 pl-3 text-right whitespace-nowrap">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-muted-foreground hover:text-indigo-600"
                                                    >
                                                        <Link
                                                            href={edit(
                                                                client.id,
                                                            )}
                                                            title="Edit client"
                                                            aria-label={`Edit ${client.name}`}
                                                        >
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                    <DeleteButton
                                                        deleteUrl={
                                                            destroy(client.id)
                                                                .url
                                                        }
                                                        resourceName={
                                                            client.name
                                                        }
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {clients.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="text-muted-foreground px-6 py-12 text-center"
                                        >
                                            No clients found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

ClientsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Clients', href: index() },
    ],
};
