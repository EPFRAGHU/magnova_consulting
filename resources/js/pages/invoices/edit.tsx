import { Form, Head } from '@inertiajs/react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SelectNative } from '@/components/ui/select-native';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/invoices';

type Invoice = {
    id: number;
    invoice_no: string;
    ref_no: string | null;
    invoice_date: string;
    due_date: string | null;
    billing_period_start: string | null;
    billing_period_end: string | null;
    place_of_supply: string | null;
    reverse_charge: boolean;
    status: string;
};

export default function InvoiceEdit({ invoice }: { invoice: Invoice }) {
    return (
        <>
            <Head title={`Edit Invoice ${invoice.invoice_no}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title={`Edit Invoice ${invoice.invoice_no}`}
                    description="These header fields don't affect the calculated figures — regenerate from Wages to recompute those."
                />

                <Form
                    {...InvoiceController.update.form(invoice.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="invoice_no">
                                        Invoice Number
                                    </Label>
                                    <Input
                                        id="invoice_no"
                                        name="invoice_no"
                                        defaultValue={invoice.invoice_no}
                                        required
                                    />
                                    <InputError message={errors.invoice_no} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="ref_no">Ref No.</Label>
                                    <Input
                                        id="ref_no"
                                        name="ref_no"
                                        defaultValue={invoice.ref_no ?? ''}
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Leave blank to print the client&apos;s
                                        GEMC No.
                                    </p>
                                    <InputError message={errors.ref_no} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="invoice_date">
                                        Invoice Date
                                    </Label>
                                    <Input
                                        id="invoice_date"
                                        name="invoice_date"
                                        type="date"
                                        defaultValue={invoice.invoice_date}
                                        required
                                    />
                                    <InputError message={errors.invoice_date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="due_date">Due Date</Label>
                                    <Input
                                        id="due_date"
                                        name="due_date"
                                        type="date"
                                        defaultValue={invoice.due_date ?? ''}
                                    />
                                    <InputError message={errors.due_date} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="billing_period_start">
                                        Billing Period From
                                    </Label>
                                    <Input
                                        id="billing_period_start"
                                        name="billing_period_start"
                                        type="date"
                                        defaultValue={
                                            invoice.billing_period_start ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.billing_period_start}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="billing_period_end">
                                        Billing Period To
                                    </Label>
                                    <Input
                                        id="billing_period_end"
                                        name="billing_period_end"
                                        type="date"
                                        defaultValue={
                                            invoice.billing_period_end ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.billing_period_end}
                                    />
                                </div>
                                <p className="text-muted-foreground text-xs sm:col-span-2">
                                    Leave both blank to print the whole month of
                                    the invoice as the billing period.
                                </p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="place_of_supply">
                                        Place of Supply
                                    </Label>
                                    <Input
                                        id="place_of_supply"
                                        name="place_of_supply"
                                        placeholder="e.g. Odisha"
                                        defaultValue={
                                            invoice.place_of_supply ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.place_of_supply}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <SelectNative
                                        id="status"
                                        name="status"
                                        defaultValue={invoice.status}
                                        required
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="sent">Sent</option>
                                        <option value="paid">Paid</option>
                                    </SelectNative>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="reverse_charge"
                                    name="reverse_charge"
                                    defaultChecked={invoice.reverse_charge}
                                />
                                <Label htmlFor="reverse_charge">
                                    Reverse Charge Applicable
                                </Label>
                            </div>
                            <InputError message={errors.reverse_charge} />

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={show(invoice.id).url}>Cancel</a>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

InvoiceEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Invoices', href: index() },
    ],
};
