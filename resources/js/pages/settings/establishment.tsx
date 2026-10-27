import { Form, Head } from '@inertiajs/react';
import EstablishmentController from '@/actions/App/Http/Controllers/Settings/EstablishmentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/establishment';

type Establishment = {
    name: string;
    gstin: string | null;
    pan: string | null;
    pf_establishment_code: string | null;
    establishment_address1: string | null;
    establishment_address2: string | null;
    establishment_city: string | null;
    establishment_district: string | null;
    establishment_pin: string | null;
    establishment_mobile: string | null;
    establishment_email: string | null;
    establishment_website: string | null;
    bank_name: string | null;
    bank_account_no: string | null;
    bank_ifsc: string | null;
    bank_branch: string | null;
};

type Props = { establishment: Establishment };

const bankFields: Array<{ name: keyof Establishment; label: string }> = [
    { name: 'bank_name', label: 'Name of the bank' },
    { name: 'bank_account_no', label: 'Account number' },
    { name: 'bank_ifsc', label: 'IFSC code' },
    { name: 'bank_branch', label: 'Branch (with PIN)' },
];

const fields: Array<{
    name: keyof Establishment;
    label: string;
    type?: string;
    required?: boolean;
}> = [
    { name: 'name', label: 'Establishment name', required: true },
    { name: 'gstin', label: 'GSTIN' },
    { name: 'pan', label: 'PAN' },
    {
        name: 'pf_establishment_code',
        label: 'PF establishment code (for the PF A/C number on payslips)',
    },
    { name: 'establishment_address1', label: 'Address line 1' },
    { name: 'establishment_address2', label: 'Address line 2' },
    { name: 'establishment_city', label: 'City' },
    { name: 'establishment_district', label: 'District' },
    { name: 'establishment_pin', label: 'PIN' },
    { name: 'establishment_mobile', label: 'Mobile', type: 'tel' },
    { name: 'establishment_email', label: 'Email ID', type: 'email' },
    { name: 'establishment_website', label: 'Website', type: 'url' },
];

export default function Establishment({ establishment }: Props) {
    return (
        <>
            <Head title="Establishment settings" />
            <h1 className="sr-only">Establishment settings</h1>
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Establishment details"
                    description="Update the address and contact details used across your business documents"
                />
                <Form
                    {...EstablishmentController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6 sm:grid-cols-2">
                                {fields.map((field) => (
                                    <div
                                        className="grid gap-2"
                                        key={field.name}
                                    >
                                        <Label htmlFor={field.name}>
                                            {field.label}
                                        </Label>
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            type={field.type ?? 'text'}
                                            defaultValue={
                                                establishment[field.name] ?? ''
                                            }
                                            required={field.required}
                                            autoComplete="off"
                                        />
                                        <InputError
                                            message={errors[field.name]}
                                        />
                                    </div>
                                ))}
                            </div>
                            <Heading
                                variant="small"
                                title="Bank details"
                                description="Shown in the Bank Details box on every tax invoice"
                            />
                            <div className="grid gap-6 sm:grid-cols-2">
                                {bankFields.map((field) => (
                                    <div
                                        className="grid gap-2"
                                        key={field.name}
                                    >
                                        <Label htmlFor={field.name}>
                                            {field.label}
                                        </Label>
                                        <Input
                                            id={field.name}
                                            name={field.name}
                                            type="text"
                                            defaultValue={
                                                establishment[field.name] ?? ''
                                            }
                                            autoComplete="off"
                                        />
                                        <InputError
                                            message={errors[field.name]}
                                        />
                                    </div>
                                ))}
                            </div>
                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>Save</Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Establishment.layout = {
    breadcrumbs: [{ title: 'Establishment settings', href: edit() }],
};
