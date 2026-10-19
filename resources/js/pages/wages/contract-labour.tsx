import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar, MONTHS } from '@/components/filter-bar';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { pdf, show, store as storeInvoice } from '@/routes/invoices';
import { index } from '@/routes/wages';
import { nil } from '@/routes/registers/contract';
import { register, save } from '@/routes/wages/contract';

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

type Figures = Record<string, number>;

type Row = {
    employee_id: number;
    serial: number;
    name: string;
    category: string;
    uan: string | null;
    esic_no: string | null;
    rate: number;
    paid_days: number;
    ot_hours: number;
    figures: Figures;
    saved: boolean;
    stale: boolean;
};

type Totals = { paid_days: number; ot_hours: number; figures: Figures };

type InvoiceInfo = { id: number; invoice_no: string; status: string; grand_total: string } | null;

const money = (value: number | undefined) =>
    (value ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const days = (value: number) => (Number.isInteger(value) ? String(value) : value.toFixed(2).replace(/0$/, ''));

export default function ContractWages({
    client,
    rows,
    totals,
    headings,
    clients,
    client_id: clientId,
    month,
    year,
    invoice,
    invoice_prefix: invoicePrefix,
    can_invoice: canInvoice,
}: {
    client: ClientInfo;
    rows: Row[];
    totals: Totals;
    headings: Record<string, string>;
    clients: ClientOption[];
    client_id: number;
    month: number;
    year: number;
    invoice: InvoiceInfo;
    invoice_prefix: string;
    can_invoice: boolean;
}) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [invoiceNumber, setInvoiceNumber] = useState('');
    const [invoicing, setInvoicing] = useState(false);
    const [processing, setProcessing] = useState(false);
    const unsaved = rows.filter((row) => !row.saved).length;
    const stale = rows.filter((row) => row.stale).length;
    const query = new URLSearchParams({ client_id: String(clientId), month: String(month), year: String(year) }).toString();

    const saveSheet = () => {
        setProcessing(true);
        router.post(save().url, { client_id: clientId, month, year }, { preserveScroll: true, onFinish: () => setProcessing(false) });
    };

    const generateInvoice = () => {
        setInvoicing(true);
        router.post(
            storeInvoice().url,
            { client_id: clientId, month, year, invoice_number: invoiceNumber },
            { preserveScroll: true, onFinish: () => setInvoicing(false) },
        );
    };

    // Column keys follow the client's own wage sheet, left to right.
    const left: [string, (row: Row) => string][] = [
        ['Wages per day', (r) => days(r.rate)],
        ['Present days', (r) => days(r.paid_days)],
    ];
    const figureColumns: [string, string][] = [
        ['Earn wages', 'earn_wages'],
        ['OT', ''],
        ['OT amount', 'ot_amount'],
        ['Bonus', 'bonus'],
        ['Leave wages', 'leave_wages'],
        ['PMJJBY', 'pmjjby'],
        ['PMSBY', 'pmsby'],
        ['Gross', 'gross'],
        [headings.pf_employee, 'pf_employee'],
        [headings.esic_employee, 'esic_employee'],
        ['Total deduction', 'total_deduction'],
        ['Net salary', 'net_salary'],
        [headings.pf_employer, 'pf_employer'],
        [headings.esic_employer, 'esic_employer'],
        ['PPE', 'ppe'],
        ['LWF', 'lwf'],
        ['Total contribution', 'total_contribution'],
        ['Total CTC', 'total_ctc'],
        ['Service charges', 'service_charge'],
        ['Gross amount', 'gross_amount'],
        ['GST', 'gst'],
        ['Amount incl. GST', 'amount_incl_gst'],
    ];

    return (
        <>
            <Head title="Wages" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading title="Register of Wages" description="Calculated from the attendance register and this client's rates, in the client's own layout." />
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <a href={`${register().url}?${query}`}>
                                <Download /> Download Register of Wages
                            </a>
                        </Button>
                        <Button asChild variant="outline">
                            <a href={`${nil('fines').url}?${query}`}>
                                <Download /> Register of Fines
                            </a>
                        </Button>
                        <Button asChild variant="outline">
                            <a href={`${nil('deductions').url}?${query}`}>
                                <Download /> Register of Deductions
                            </a>
                        </Button>
                        <Button onClick={saveSheet} disabled={processing || rows.length === 0}>
                            {processing ? 'Saving...' : 'Calculate & Save Wages'}
                        </Button>
                    </div>
                </div>

                <FilterBar url={index().url} clients={clients} clientId={clientId} month={month} year={year} />

                <div className="bg-card rounded-md border p-3 text-sm">
                    <div className="font-medium">Contractor: {client.register_contractor_address ?? '-'}</div>
                    <div>Principal employer: {client.principal_employer_name ?? '-'}</div>
                    <div className="text-muted-foreground">Month: {MONTHS[month - 1]}, {year}</div>
                </div>

                <div className="bg-card flex flex-wrap items-end gap-3 rounded-md border p-3 text-sm">
                    <div className="font-medium">Tax invoice</div>
                    {invoice ? (
                        <>
                            <div>
                                <strong>{invoice.invoice_no}</strong>{' '}
                                <span className="text-muted-foreground">({invoice.status}) · Rs. {money(Number(invoice.grand_total))}</span>
                            </div>
                            <Button asChild variant="outline" size="sm">
                                <Link href={show(invoice.id)}>View invoice</Link>
                            </Button>
                            <Button asChild variant="outline" size="sm">
                                <a href={pdf(invoice.id).url}>
                                    <Download /> Download PDF
                                </a>
                            </Button>
                            {invoice.status === 'draft' && (
                                <Button size="sm" onClick={generateInvoice} disabled={invoicing || !canInvoice}>
                                    {invoicing ? 'Updating...' : 'Update invoice from the saved wages'}
                                </Button>
                            )}
                        </>
                    ) : (
                        <>
                            <div className="flex items-center gap-1">
                                <span className="font-medium whitespace-nowrap">Invoice No. {invoicePrefix}</span>
                                <Input
                                    value={invoiceNumber}
                                    onChange={(e) => setInvoiceNumber(e.target.value)}
                                    placeholder="___"
                                    maxLength={20}
                                    className="h-8 w-24"
                                    aria-label="Invoice number"
                                />
                            </div>
                            <Button size="sm" onClick={generateInvoice} disabled={invoicing || !canInvoice}>
                                {invoicing ? 'Generating...' : 'Generate Tax Invoice'}
                            </Button>
                        </>
                    )}
                    {!canInvoice && rows.length > 0 && (
                        <span className="text-muted-foreground">Save the wages for every employee first (and again after any attendance change).</span>
                    )}
                    <InputError message={errors.invoice_number ?? errors.month} className="basis-full" />
                </div>

                {unsaved > 0 && (
                    <div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-950/40">
                        {unsaved === rows.length ? 'These wages are a preview and are not saved yet.' : `${unsaved} employee${unsaved === 1 ? '' : 's'} not saved yet.`}{' '}
                        Press &quot;Calculate &amp; Save Wages&quot; to save them.
                    </div>
                )}
                {stale > 0 && (
                    <div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-950/40">
                        Attendance or rates changed after {stale} line{stale === 1 ? ' was' : 's were'} saved (marked &quot;changed&quot;). Press &quot;Calculate &amp; Save Wages&quot; to update them.
                    </div>
                )}

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-xs whitespace-nowrap">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground text-left">
                                <th className="px-2 py-2 font-medium">S.No.</th>
                                <th className="bg-card sticky left-0 z-10 px-2 py-2 font-medium">Name</th>
                                <th className="px-2 py-2 font-medium">Category</th>
                                <th className="px-2 py-2 font-medium">UAN No</th>
                                <th className="px-2 py-2 font-medium">ESIC No</th>
                                {left.map(([label]) => (
                                    <th key={label} className="px-2 py-2 text-right font-medium">{label}</th>
                                ))}
                                {figureColumns.map(([label]) => (
                                    <th key={label} className="px-2 py-2 text-right font-medium">{label}</th>
                                ))}
                                <th className="px-2 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.employee_id} className="border-t">
                                    <td className="px-2 py-1.5">{row.serial}</td>
                                    <td className="bg-card sticky left-0 z-10 px-2 py-1.5 font-medium">{row.name}</td>
                                    <td className="px-2 py-1.5">{row.category}</td>
                                    <td className="px-2 py-1.5">{row.uan}</td>
                                    <td className="px-2 py-1.5">{row.esic_no}</td>
                                    {left.map(([label, value]) => (
                                        <td key={label} className="px-2 py-1.5 text-right">{value(row)}</td>
                                    ))}
                                    {figureColumns.map(([label, key]) => (
                                        <td key={label} className="px-2 py-1.5 text-right">
                                            {key === '' ? days(row.ot_hours) : money(row.figures[key])}
                                        </td>
                                    ))}
                                    <td className="px-2 py-1.5">
                                        {!row.saved ? (
                                            <span className="text-muted-foreground">preview</span>
                                        ) : row.stale ? (
                                            <span className="font-medium text-amber-600">changed</span>
                                        ) : (
                                            <span className="text-green-600">saved</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td colSpan={figureColumns.length + left.length + 6} className="text-muted-foreground px-6 py-8 text-center">
                                        No active employees in this unit yet.
                                    </td>
                                </tr>
                            )}
                            {rows.length > 0 && (
                                <tr className="bg-muted/40 border-t font-semibold">
                                    <td colSpan={5} className="px-2 py-2">Total</td>
                                    <td />
                                    <td className="px-2 py-2 text-right">{days(totals.paid_days)}</td>
                                    {figureColumns.map(([label, key]) => (
                                        <td key={label} className="px-2 py-2 text-right">
                                            {key === '' ? days(totals.ot_hours) : money(totals.figures[key])}
                                        </td>
                                    ))}
                                    <td />
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

ContractWages.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Wages', href: index() },
    ],
};
