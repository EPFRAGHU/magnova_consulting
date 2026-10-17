import { Form, Head } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import { index } from '@/routes/clients';

type Client = {
    id: number;
    name: string;
    unit_label: string | null;
    address: string | null;
    gstin: string | null;
    sac_code: string | null;
    gemc_no: string | null;
    billing_name: string | null;
    billing_address: string | null;
    contact_person: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    active: boolean;
    bonus_enabled: boolean;
    service_charge_on_basic: boolean;
    gst_exempt: boolean;
    esic_on_ceiling: boolean;
};

export default function ClientForm({ client }: { client?: Client }) {
    const isEdit = !!client;
    const submitOptions = isEdit
        ? ClientController.update.form(client!.id)
        : ClientController.store.form();

    return (
        <>
            <Head title={isEdit ? 'Edit Client' : 'Add Client'} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title={isEdit ? 'Edit Client' : 'Add Client'}
                    description="Establishment details used on invoices and payslips"
                />

                <Form {...submitOptions} className="max-w-2xl space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={client?.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="unit_label">
                                        Unit / Site Label
                                    </Label>
                                    <Input
                                        id="unit_label"
                                        name="unit_label"
                                        defaultValue={client?.unit_label ?? ''}
                                    />
                                    <InputError message={errors.unit_label} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address">Address</Label>
                                <Textarea
                                    id="address"
                                    name="address"
                                    defaultValue={client?.address ?? ''}
                                    rows={3}
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_name">
                                    Billing Name{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (only if a parent organization pays the
                                        bill instead of this client — leave
                                        blank otherwise)
                                    </span>
                                </Label>
                                <Input
                                    id="billing_name"
                                    name="billing_name"
                                    placeholder="e.g. The Head, Visakhapatnam Regional Centre of CMFRI"
                                    defaultValue={client?.billing_name ?? ''}
                                />
                                <InputError message={errors.billing_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_address">
                                    Billing Address
                                </Label>
                                <Textarea
                                    id="billing_address"
                                    name="billing_address"
                                    defaultValue={client?.billing_address ?? ''}
                                    rows={3}
                                />
                                <InputError message={errors.billing_address} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="gstin">GSTIN</Label>
                                    <Input
                                        id="gstin"
                                        name="gstin"
                                        defaultValue={client?.gstin ?? ''}
                                    />
                                    <InputError message={errors.gstin} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="sac_code">SAC Code</Label>
                                    <Input
                                        id="sac_code"
                                        name="sac_code"
                                        defaultValue={client?.sac_code ?? ''}
                                    />
                                    <InputError message={errors.sac_code} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gemc_no">GEMC No.</Label>
                                    <Input
                                        id="gemc_no"
                                        name="gemc_no"
                                        placeholder="GeM contract number"
                                        defaultValue={client?.gemc_no ?? ''}
                                    />
                                    <InputError message={errors.gemc_no} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_person">
                                        Contact Person
                                    </Label>
                                    <Input
                                        id="contact_person"
                                        name="contact_person"
                                        defaultValue={
                                            client?.contact_person ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.contact_person}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_phone">
                                        Contact Phone
                                    </Label>
                                    <Input
                                        id="contact_phone"
                                        name="contact_phone"
                                        defaultValue={
                                            client?.contact_phone ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.contact_phone}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_email">
                                        Contact Email
                                    </Label>
                                    <Input
                                        id="contact_email"
                                        type="email"
                                        name="contact_email"
                                        defaultValue={
                                            client?.contact_email ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.contact_email}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="active"
                                        name="active"
                                        defaultChecked={client?.active ?? true}
                                    />
                                    <Label htmlFor="active">Active</Label>
                                </div>
                                <InputError message={errors.active} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="bonus_enabled"
                                        name="bonus_enabled"
                                        defaultChecked={
                                            client?.bonus_enabled ?? false
                                        }
                                    />
                                    <Label htmlFor="bonus_enabled">
                                        Bonus applicable{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (when ticked, every employee of this
                                            unit gets bonus in wages, payslips
                                            and invoices)
                                        </span>
                                    </Label>
                                </div>
                                <InputError message={errors.bonus_enabled} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="service_charge_on_basic"
                                        name="service_charge_on_basic"
                                        defaultChecked={
                                            client?.service_charge_on_basic ??
                                            false
                                        }
                                    />
                                    <Label htmlFor="service_charge_on_basic">
                                        Service charge on Basic Wages{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (ticked: service charge is worked
                                            out on Basic Wages. Unticked: on
                                            Gross Wages, including EPF, ESIC
                                            and bonus)
                                        </span>
                                    </Label>
                                </div>
                                <InputError
                                    message={errors.service_charge_on_basic}
                                />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="gst_exempt"
                                        name="gst_exempt"
                                        defaultChecked={
                                            client?.gst_exempt ?? false
                                        }
                                    />
                                    <Label htmlFor="gst_exempt">
                                        No GST{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (ticked: CGST and SGST are 0 in
                                            this unit's invoices and register.
                                            For educational institutions,
                                            government departments and other
                                            exempt clients)
                                        </span>
                                    </Label>
                                </div>
                                <InputError message={errors.gst_exempt} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="esic_on_ceiling"
                                        name="esic_on_ceiling"
                                        defaultChecked={
                                            client?.esic_on_ceiling ?? false
                                        }
                                    />
                                    <Label htmlFor="esic_on_ceiling">
                                        ESIC on the wage ceiling{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (ticked: every employee of this
                                            unit gets ESIC on the ceiling,
                                            Rs. 21,000, even when wages are
                                            above it. This is an arrangement
                                            with this client, not the ESI
                                            Act rule. Other units are not
                                            affected.)
                                        </span>
                                    </Label>
                                </div>
                                <InputError message={errors.esic_on_ceiling} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    {isEdit ? 'Save Changes' : 'Create Client'}
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={index().url}>Cancel</a>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ClientForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Clients', href: index() },
        { title: 'Client Form', href: index() },
    ],
};
