import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { DeleteButton } from '@/components/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import {
    create,
    destroy,
    edit,
    index,
    regenerateInvoices,
} from '@/routes/clients';

// Solid status chips, the same style as the blue "Active" badge.
const CHIP = {
    green: 'border-transparent bg-emerald-600 text-white dark:bg-emerald-500',
    slate: 'border-transparent bg-slate-500 text-white dark:bg-slate-400 dark:text-slate-950',
    violet: 'border-transparent bg-violet-600 text-white dark:bg-violet-500',
    amber: 'border-transparent bg-amber-500 text-white dark:bg-amber-400 dark:text-amber-950',
    cyan: 'border-transparent bg-cyan-600 text-white dark:bg-cyan-500',
    rose: 'border-transparent bg-rose-600 text-white dark:bg-rose-500',
} as const;

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

export default function ClientsIndex({
    clients,
    search,
    regenerate,
}: {
    clients: ClientRow[];
    search: string;
    regenerate: RegenerateAsk | null;
}) {
    const [query, setQuery] = useState(search ?? '');
    const [asking, setAsking] = useState(regenerate !== null);
    const [working, setWorking] = useState(false);

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

    const runSearch = (value: string) => {
        setQuery(value);
        router.get(
            index().url,
            { search: value },
            { preserveState: true, replace: true },
        );
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
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Clients"
                        description="Establishments Magnova supplies manpower to"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Add Client
                        </Link>
                    </Button>
                </div>

                <Input
                    placeholder="Search clients..."
                    value={query}
                    onChange={(e) => runSearch(e.target.value)}
                    className="max-w-sm"
                />

                <Card>
                    <CardContent className="px-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="px-6 py-2 font-medium">
                                            Name
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            GSTIN
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Contact
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Employees
                                        </th>
                                        <th className="px-6 py-2 font-medium">
                                            Status
                                        </th>
                                        <th className="px-6 py-2 font-medium" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {clients.map((client) => (
                                        <tr
                                            key={client.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-6 py-2">
                                                {client.name}
                                                {client.unit_label && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        - {client.unit_label}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-2">
                                                {client.gstin ?? '-'}
                                            </td>
                                            <td className="px-6 py-2">
                                                {client.contact_person ?? '-'}
                                                {client.contact_phone
                                                    ? ` (${client.contact_phone})`
                                                    : ''}
                                            </td>
                                            <td className="px-6 py-2">
                                                {client.employees_count}
                                            </td>
                                            <td className="px-6 py-2">
                                                <Badge
                                                    variant={
                                                        client.active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {client.active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </Badge>{' '}
                                                <Badge
                                                    className={
                                                        client.bonus_enabled
                                                            ? CHIP.green
                                                            : CHIP.slate
                                                    }
                                                >
                                                    {client.bonus_enabled
                                                        ? 'Bonus on'
                                                        : 'Bonus off'}
                                                </Badge>{' '}
                                                <Badge
                                                    className={
                                                        client.service_charge_on_basic
                                                            ? CHIP.violet
                                                            : CHIP.amber
                                                    }
                                                >
                                                    {client.service_charge_on_basic
                                                        ? 'Service on Basic'
                                                        : 'Service on Gross'}
                                                </Badge>{' '}
                                                <Badge
                                                    className={
                                                        client.gst_exempt
                                                            ? CHIP.rose
                                                            : CHIP.cyan
                                                    }
                                                >
                                                    {client.gst_exempt
                                                        ? 'No GST'
                                                        : 'GST on'}
                                                </Badge>
                                                {client.payroll_profile ===
                                                    'contract_labour' && (
                                                    <>
                                                        {' '}
                                                        <Badge
                                                            className={
                                                                CHIP.amber
                                                            }
                                                        >
                                                            Contract labour
                                                        </Badge>
                                                    </>
                                                )}
                                                {client.esic_unit_mode !==
                                                    'statutory' && (
                                                    <>
                                                        {' '}
                                                        <Badge
                                                            className={
                                                                client.esic_unit_mode ===
                                                                'ceiling'
                                                                    ? CHIP.violet
                                                                    : CHIP.slate
                                                            }
                                                        >
                                                            {client.esic_unit_mode ===
                                                            'ceiling'
                                                                ? 'ESIC on ceiling'
                                                                : 'No ESIC above ceiling'}
                                                        </Badge>
                                                    </>
                                                )}
                                            </td>
                                            <td className="px-6 py-2 text-right whitespace-nowrap">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={edit(client.id)}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <DeleteButton
                                                    deleteUrl={
                                                        destroy(client.id).url
                                                    }
                                                    resourceName={client.name}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                    {clients.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="text-muted-foreground px-6 py-6 text-center"
                                            >
                                                No clients found.
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

ClientsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Clients', href: index() },
    ],
};
