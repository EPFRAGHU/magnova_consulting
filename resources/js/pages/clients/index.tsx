import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { DeleteButton } from '@/components/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { create, destroy, edit, index } from '@/routes/clients';

type ClientRow = {
    id: number;
    name: string;
    unit_label: string | null;
    gstin: string | null;
    contact_person: string | null;
    contact_phone: string | null;
    active: boolean;
    bonus_enabled: boolean;
    employees_count: number;
};

export default function ClientsIndex({
    clients,
    search,
}: {
    clients: ClientRow[];
    search: string;
}) {
    const [query, setQuery] = useState(search ?? '');

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
                                                <Badge variant="outline">
                                                    {client.bonus_enabled
                                                        ? 'Bonus on'
                                                        : 'Bonus off'}
                                                </Badge>
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
