import { useState } from 'react';
import { Form, Head } from '@inertiajs/react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SelectNative } from '@/components/ui/select-native';
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
    esic_unit_mode: string;
    payroll_profile: string;
    principal_employer_name: string | null;
    principal_employer_address: string | null;
    principal_employer_gstin: string | null;
    contract_start: string | null;
    contract_end: string | null;
    register_contractor_address: string | null;
    buyer_order_no: string | null;
    buyer_order_date: string | null;
};

export default function ClientForm({ client }: { client?: Client }) {
    const isEdit = !!client;
    const [profile, setProfile] = useState(client?.payroll_profile ?? 'standard');
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

                            <div className="grid gap-4 rounded-md border p-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="payroll_profile">
                                        How is this client billed?
                                    </Label>
                                    <SelectNative
                                        id="payroll_profile"
                                        name="payroll_profile"
                                        value={profile}
                                        onChange={(e) =>
                                            setProfile(e.target.value)
                                        }
                                        className="max-w-md"
                                    >
                                        <option value="standard">
                                            Standard (regular unit)
                                        </option>
                                        <option value="contract_labour">
                                            Contract labour (principal employer)
                                        </option>
                                    </SelectNative>
                                    <p className="text-muted-foreground text-xs">
                                        A contract labour client has its own
                                        attendance register, wage sheet and tax
                                        invoice layout. Regular units are not
                                        affected.
                                    </p>
                                    <InputError message={errors.payroll_profile} />
                                </div>

                                {profile === 'contract_labour' && (
                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="principal_employer_name">
                                                Principal employer
                                            </Label>
                                            <Input
                                                id="principal_employer_name"
                                                name="principal_employer_name"
                                                defaultValue={client?.principal_employer_name ?? ''}
                                                required
                                            />
                                            <InputError message={errors.principal_employer_name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="principal_employer_address">
                                                Principal employer address
                                            </Label>
                                            <Textarea
                                                id="principal_employer_address"
                                                name="principal_employer_address"
                                                rows={2}
                                                defaultValue={client?.principal_employer_address ?? ''}
                                            />
                                            <InputError message={errors.principal_employer_address} />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-3">
                                            <div className="grid gap-2">
                                                <Label htmlFor="principal_employer_gstin">
                                                    Principal employer GSTIN
                                                </Label>
                                                <Input
                                                    id="principal_employer_gstin"
                                                    name="principal_employer_gstin"
                                                    defaultValue={client?.principal_employer_gstin ?? ''}
                                                />
                                                <InputError message={errors.principal_employer_gstin} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="contract_start">
                                                    Contract starts
                                                </Label>
                                                <Input
                                                    id="contract_start"
                                                    name="contract_start"
                                                    type="date"
                                                    defaultValue={client?.contract_start ?? ''}
                                                />
                                                <InputError message={errors.contract_start} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="contract_end">
                                                    Contract ends
                                                </Label>
                                                <Input
                                                    id="contract_end"
                                                    name="contract_end"
                                                    type="date"
                                                    defaultValue={client?.contract_end ?? ''}
                                                />
                                                <InputError message={errors.contract_end} />
                                            </div>
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="buyer_order_no">
                                                    Buyer's order / PO number
                                                </Label>
                                                <Input
                                                    id="buyer_order_no"
                                                    name="buyer_order_no"
                                                    defaultValue={client?.buyer_order_no ?? ''}
                                                />
                                                <InputError message={errors.buyer_order_no} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="buyer_order_date">
                                                    Order date
                                                </Label>
                                                <Input
                                                    id="buyer_order_date"
                                                    name="buyer_order_date"
                                                    type="date"
                                                    defaultValue={client?.buyer_order_date ?? ''}
                                                />
                                                <InputError message={errors.buyer_order_date} />
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="register_contractor_address">
                                                Contractor address for the registers
                                            </Label>
                                            <Textarea
                                                id="register_contractor_address"
                                                name="register_contractor_address"
                                                rows={2}
                                                defaultValue={client?.register_contractor_address ?? ''}
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                Printed under &quot;Contractor name&quot; on this client&apos;s
                                                registers. It can differ from the office address in Settings.
                                            </p>
                                            <InputError message={errors.register_contractor_address} />
                                        </div>
                                    </div>
                                )}
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
                                <Label htmlFor="esic_unit_mode">
                                    ESIC for this unit
                                </Label>
                                <SelectNative
                                    id="esic_unit_mode"
                                    name="esic_unit_mode"
                                    defaultValue={
                                        client?.esic_unit_mode ?? 'statutory'
                                    }
                                    className="max-w-md"
                                >
                                    <option value="statutory">
                                        Follow the ESIC rules (statutory)
                                    </option>
                                    <option value="ceiling">
                                        ESIC on the wage ceiling, even above it
                                    </option>
                                    <option value="cutoff">
                                        No ESIC when wages are above the ceiling
                                    </option>
                                </SelectNative>
                                <p className="text-muted-foreground text-xs">
                                    The last two are arrangements with this
                                    client, not the ESI Act rule. With the
                                    ceiling at Rs. 21,000, &quot;on the
                                    ceiling&quot; charges ESIC on Rs. 21,000
                                    for everyone in this unit, and &quot;no
                                    ESIC above the ceiling&quot; charges
                                    nothing for anyone above it, even if they
                                    were covered earlier. Other units are not
                                    affected.
                                </p>
                                <InputError message={errors.esic_unit_mode} />
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
