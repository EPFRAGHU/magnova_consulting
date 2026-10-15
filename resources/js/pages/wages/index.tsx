import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { FilterBar, MONTHS } from '@/components/filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index, register, store } from '@/routes/wages';

type ClientOption = { id: number; name: string; unit_label: string | null };

type Row = {
    serial_number: number;
    employee_id: number;
    name: string;
    employee_code: string | null;
    nature_of_deployment: string;
    sac_code: string;
    sac_description: string | null;
    days_deployed: number;
    minimum_wage: number;
    monthly_wage: number;
    epf_employer: number;
    edli_and_epf_admin: number;
    esic_employer: number;
    bonus: number;
    total_gross_salary: number;
    gst: number;
    agency_fee: number;
    total_bill_amount: number;
    epf_employee: number;
    esic_employee: number;
    other_deduction: number;
    net_payable_salary: number;
    saved: boolean;
};

type Headings = {
    agency: string;
    epf: string;
    edli: string;
    esic: string;
    bonus: string;
    epf_individual: string;
    esic_individual: string;
};

export default function WagesIndex({
    rows,
    clients,
    client_id: clientId,
    month,
    year,
    headings,
}: {
    rows: Row[];
    clients: ClientOption[];
    client_id: number | null;
    month: number;
    year: number;
    headings: Headings;
}) {
    const [processing, setProcessing] = useState(false);
    const selectedClient = clients.find((client) => client.id === clientId);
    const monthName = MONTHS[month - 1]?.toUpperCase() ?? String(month);

    const compute = (row: Row) => ({
        monthlyWage: row.monthly_wage,
        epfEmployer: row.epf_employer,
        edliAndEpfAdmin: row.edli_and_epf_admin,
        esicEmployer: row.esic_employer,
        bonus: row.bonus,
        totalGrossSalary: row.total_gross_salary,
        gst: row.gst,
        agencyFee: row.agency_fee,
        totalBillAmount: row.total_bill_amount,
        epf: row.epf_employee,
        esic: row.esic_employee,
        net: row.net_payable_salary,
    });

    const calculatedRows = rows.map((row) => ({ row, values: compute(row) }));
    const totals = calculatedRows.reduce(
        (total, { row, values }) => ({
            days: total.days + row.days_deployed,
            monthlyWage: total.monthlyWage + values.monthlyWage,
            epfEmployer: total.epfEmployer + values.epfEmployer,
            edliAndEpfAdmin: total.edliAndEpfAdmin + values.edliAndEpfAdmin,
            esicEmployer: total.esicEmployer + values.esicEmployer,
            bonus: total.bonus + values.bonus,
            totalGrossSalary: total.totalGrossSalary + values.totalGrossSalary,
            gst: total.gst + values.gst,
            agencyFee: total.agencyFee + values.agencyFee,
            totalBillAmount: total.totalBillAmount + values.totalBillAmount,
            epf: total.epf + values.epf,
            esic: total.esic + values.esic,
            otherDeduction: total.otherDeduction + row.other_deduction,
            net: total.net + values.net,
        }),
        {
            days: 0, monthlyWage: 0, epfEmployer: 0, edliAndEpfAdmin: 0,
            esicEmployer: 0, bonus: 0, totalGrossSalary: 0, gst: 0,
            agencyFee: 0, totalBillAmount: 0, epf: 0, esic: 0,
            otherDeduction: 0, net: 0,
        },
    );

    const save = () => {
        setProcessing(true);
        router.post(
            store().url,
            { month, year, rows: rows.map((row) => ({ employee_id: row.employee_id })) },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    };

    return (
        <>
            <Head title="Wages" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Wages" description="Register of wages calculated from attendance" />
                    <div className="flex gap-2">
                        {clientId && (
                            <Button asChild variant="outline">
                                <a href={`${register().url}?${new URLSearchParams({ client_id: String(clientId), month: String(month), year: String(year) }).toString()}`}>
                                    <Download /> Register of Wages
                                </a>
                            </Button>
                        )}
                        <Button onClick={save} disabled={processing || rows.length === 0}>Save Wages for Month</Button>
                    </div>
                </div>

                <FilterBar url={index().url} clients={clients} clientId={clientId} month={month} year={year} />

                <Card>
                    <CardContent className="px-0">
                        <div className="border-b px-3 pb-2 pt-3 text-center">
                            <div className="text-sm font-bold">REGISTER OF WAGES</div>
                            <div className="text-[10px] font-semibold">NAME OF THE ESTABLISHMENT : M/S MAGNOVA CONSULTING PVT. LTD.</div>
                            <div className="text-[10px] font-semibold">SALARY SHEET FOR THE MONTH OF {monthName}, {year}</div>
                            <div className="text-[10px] font-semibold">
                                UNIT: {selectedClient?.name ?? 'ALL CLIENTS'}{selectedClient?.unit_label ? ` - ${selectedClient.unit_label}` : ''}
                            </div>
                        </div>
                        <div className="w-full overflow-hidden">
                            <table className="w-full table-fixed border-collapse border border-slate-300 text-[8px] [&_th]:border [&_th]:border-slate-300 [&_th]:text-center [&_th]:font-bold [&_td]:border [&_td]:border-slate-200 dark:border-slate-600 dark:[&_th]:border-slate-600 dark:[&_td]:border-slate-700">
                                <colgroup>
                                    {[2, 9, 8, 4, 5, 5, 5, 6, 5, 5, 6, 4, 5, 6, 6, 6, 4, 6, 3].map((width, index) => (
                                        <col key={index} style={{ width: `${width}%` }} />
                                    ))}
                                </colgroup>
                                <thead>
                                    <tr className="border-b bg-muted/50 text-center font-semibold">
                                        {Array.from({ length: 18 }, (_, index) => <th key={index} className="px-1 py-1">{String.fromCharCode(65 + index)}</th>)}
                                        <th className="px-1 py-1" />
                                    </tr>
                                    <tr className="border-b bg-muted/30 text-center font-semibold">
                                        <th className="px-1 py-1" />
                                        <th colSpan={17} className="px-1 py-1">Particular</th>
                                        <th className="px-1 py-1" />
                                    </tr>
                                    <tr className="text-muted-foreground border-b">
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Sl no.</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Name of the Guard</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Nature of Deployment (Armed/ Unarmed)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Days Deployed in the Month</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Minimum Wage as per Central Minimum Wage Rate</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Monthly Wage (D*E)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.epf}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.edli}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.esic}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.bonus}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Total Gross salary of the Security Guards (F+G+H+I+J)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">GST @ 18 % (K*0.18)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.agency}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Total Bill amount in Rs. Including GST (K+L+M)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.epf_individual}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">{headings.esic_individual}</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Any Other Deduction (Professional Tax etc.)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight">Net Payable Salary of the Guard (F+J) - (O+P+Q)</th>
                                        <th className="px-1 py-1 align-top font-medium break-words leading-tight" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {calculatedRows.map(({ row, values }) => {
                                        return (
                                            <tr key={row.employee_id} className="border-b last:border-0">
                                                <td className="break-words px-1 py-1 align-top">{row.serial_number}</td>
                                                <td className="break-words px-1 py-1 align-top">{row.name}{row.employee_code && ` (${row.employee_code})`}</td>
                                                <td className="break-words px-1 py-1 align-top">{row.nature_of_deployment}<span className="text-muted-foreground block text-[7px]">SAC {row.sac_code}{row.sac_description ? ` · ${row.sac_description}` : ''}</span></td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(row.days_deployed)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(row.minimum_wage)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.monthlyWage)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.epfEmployer)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.edliAndEpfAdmin)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.esicEmployer)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.bonus)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.totalGrossSalary)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.gst)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.agencyFee)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.totalBillAmount)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.epf)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(values.esic)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{formatRounded(row.other_deduction)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top font-medium">{formatRounded(values.net)}</td>
                                                <td className="break-words px-1 py-1 text-center align-top">{row.saved && <Badge className="px-1 text-[7px]" variant="outline">Saved</Badge>}</td>
                                            </tr>
                                        );
                                    })}
                                    {rows.length > 0 && (
                                        <tr className="border-t-2 bg-muted/50 font-semibold">
                                            <td colSpan={3} className="px-1 py-1">Total</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.days)}</td>
                                            <td className="px-1 py-1" />
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.monthlyWage)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.epfEmployer)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.edliAndEpfAdmin)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.esicEmployer)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.bonus)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.totalGrossSalary)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.gst)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.agencyFee)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.totalBillAmount)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.epf)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.esic)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.otherDeduction)}</td>
                                            <td className="px-1 py-1 text-center">{formatRounded(totals.net)}</td>
                                            <td className="px-1 py-1" />
                                        </tr>
                                    )}
                                    {rows.length === 0 && <tr><td colSpan={20} className="text-muted-foreground px-4 py-6 text-center">No active employees for this selection.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function formatRounded(n: number) {
    const rounded = n >= 0 ? Math.floor(n + 0.5) : Math.ceil(n - 0.5);

    return String(rounded);
}

WagesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Wages', href: index() },
    ],
};