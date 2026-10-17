import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import EsicController from '@/actions/App/Http/Controllers/Settings/EsicController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { MONTHS } from '@/components/filter-bar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SelectNative } from '@/components/ui/select-native';
import { edit as editEmployee } from '@/routes/employees';
import { edit } from '@/routes/esic';

type Setting = {
    id: number | null;
    effective_from: string | null;
    establishment_covered: boolean;
    calculation_mode: string;
    coverage_ceiling: number;
    employee_rate: number;
    employer_rate: number;
    daily_wage_exemption: number | null;
    include_bonus: boolean;
    rounding: string;
    note: string | null;
};

type Props = {
    current: Setting;
    history: Setting[];
    modes: { value: string; label: string }[];
    wage_month: { month: number; year: number };
    attention: { employee_id: number; name: string | null; gross_wages: number }[];
};

const rupees = (value: number) =>
    `₹${value.toLocaleString('en-IN', { maximumFractionDigits: 2 })}`;

export default function Esic({ current, history, modes, wage_month, attention }: Props) {
    const [mode, setMode] = useState(current.calculation_mode);

    return (
        <>
            <Head title="ESIC rules" />
            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="ESIC rules"
                    description="Coverage ceiling, contribution rates and rounding. Each change applies from the month you choose; wages already saved keep the rules they were saved with."
                />

                <section className="space-y-2 rounded-md border p-3 text-sm">
                    <div className="font-medium">Rules in force now</div>
                    <ul className="text-muted-foreground list-disc space-y-1 pl-5">
                        <li>{current.establishment_covered ? 'Establishment is covered under ESIC' : 'Establishment is NOT covered: no ESIC for anyone'}</li>
                        <li>Coverage ceiling {rupees(current.coverage_ceiling)} a month (decides who becomes covered)</li>
                        <li>Employee {current.employee_rate}% · Employer {current.employer_rate}%</li>
                        <li>
                            {current.daily_wage_exemption === null
                                ? 'No daily-wage exemption'
                                : `No employee share when the daily wage is up to ${rupees(current.daily_wage_exemption)}`}
                        </li>
                        <li>Rounding: {current.rounding}, to a whole rupee</li>
                        <li>{current.include_bonus ? 'Bonus counts as ESIC wages' : 'Bonus does not count as ESIC wages'}</li>
                        <li className={current.calculation_mode !== 'statutory' ? 'font-medium text-amber-600' : ''}>
                            Mode: {modes.find((m) => m.value === current.calculation_mode)?.label}
                        </li>
                    </ul>
                </section>

                {attention.length > 0 && (
                    <section className="space-y-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-950/40">
                        <div className="font-medium">
                            Above the ceiling in {MONTHS[wage_month.month - 1]} {wage_month.year}, no ESIC taken
                        </div>
                        <p className="text-muted-foreground">
                            These employees had no earlier coverage on record, so they are treated as newly above the ceiling. If any of them was already
                            covered earlier in this contribution period, open the employee and fill in &quot;Covered under ESIC since&quot;, then save the
                            wages again.
                        </p>
                        <ul className="space-y-1">
                            {attention.map((item) => (
                                <li key={item.employee_id}>
                                    <Link href={editEmployee(item.employee_id)} className="text-primary hover:underline">
                                        {item.name}
                                    </Link>{' '}
                                    <span className="text-muted-foreground">({rupees(item.gross_wages)})</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <Form {...EsicController.store.form()} options={{ preserveScroll: true }} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <Heading variant="small" title="Change the rules" description="This adds a new dated entry; earlier entries are never edited." />

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="effective_from">Applies from</Label>
                                    <Input id="effective_from" name="effective_from" type="date" required />
                                    <p className="text-muted-foreground text-xs">Applies from the wage month containing this date (the 1st of that month). You may choose a past month, for example 1 September 2026; wages already saved keep their figures until you save them again.</p>
                                    <InputError message={errors.effective_from} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="coverage_ceiling">Coverage ceiling (₹ a month)</Label>
                                    <Input id="coverage_ceiling" name="coverage_ceiling" type="number" step="0.01" defaultValue={current.coverage_ceiling} required />
                                    <InputError message={errors.coverage_ceiling} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="employee_rate">Employee rate (%)</Label>
                                    <Input id="employee_rate" name="employee_rate" type="number" step="0.01" defaultValue={current.employee_rate} required />
                                    <InputError message={errors.employee_rate} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="employer_rate">Employer rate (%)</Label>
                                    <Input id="employer_rate" name="employer_rate" type="number" step="0.01" defaultValue={current.employer_rate} required />
                                    <InputError message={errors.employer_rate} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="daily_wage_exemption">No employee share up to a daily wage of (₹)</Label>
                                    <Input id="daily_wage_exemption" name="daily_wage_exemption" type="number" step="0.01" defaultValue={current.daily_wage_exemption ?? ''} />
                                    <p className="text-muted-foreground text-xs">Leave blank to switch the exemption off.</p>
                                    <InputError message={errors.daily_wage_exemption} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="rounding">Rounding to a whole rupee</Label>
                                    <SelectNative id="rounding" name="rounding" defaultValue={current.rounding}>
                                        <option value="nearest">Nearest rupee</option>
                                        <option value="up">Next higher rupee</option>
                                        <option value="down">Lower rupee</option>
                                    </SelectNative>
                                    <InputError message={errors.rounding} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox id="establishment_covered" name="establishment_covered" defaultChecked={current.establishment_covered} />
                                    <Label htmlFor="establishment_covered">Establishment is covered under ESIC</Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox id="include_bonus" name="include_bonus" defaultChecked={current.include_bonus} />
                                    <Label htmlFor="include_bonus">Count bonus as ESIC wages</Label>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="calculation_mode">Calculation mode</Label>
                                <SelectNative id="calculation_mode" name="calculation_mode" value={mode} onChange={(e) => setMode(e.target.value)}>
                                    {modes.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.value === 'capped'
                                                ? 'Capped at the ceiling (NOT statutory)'
                                                : m.value === 'cutoff'
                                                  ? 'No ESIC above the ceiling (NOT statutory)'
                                                  : 'Statutory (recommended)'}
                                        </option>
                                    ))}
                                </SelectNative>
                                <p className="text-muted-foreground text-xs">{modes.find((m) => m.value === mode)?.label}</p>
                                <InputError message={errors.calculation_mode} />
                            </div>

                            {mode !== 'statutory' && (
                                <div className="grid gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-950/40">
                                    <p>
                                        This mode is not the ESI Act rule. Use it only if the business has decided on it. To apply it to one unit only, use the ESIC
                                        setting on that unit in Clients instead.
                                    </p>
                                    <div className="flex items-center gap-2">
                                        <Checkbox id="confirm_capped" name="confirm_capped" />
                                        <Label htmlFor="confirm_capped">I understand this is not the statutory calculation</Label>
                                    </div>
                                    <InputError message={errors.confirm_capped} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="note">Note (optional)</Label>
                                <Input id="note" name="note" maxLength={255} placeholder="Why the rules changed" />
                                <InputError message={errors.note} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save new rules'}
                            </Button>
                        </>
                    )}
                </Form>

                <section className="space-y-2">
                    <Heading variant="small" title="History" description="Built-in defaults apply until you save your own rules." />
                    {history.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No rules saved yet; the defaults above are in use.</p>
                    ) : (
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full text-xs">
                                <thead className="bg-muted/50 text-muted-foreground text-left">
                                    <tr>
                                        <th className="px-3 py-2 font-medium">From</th>
                                        <th className="px-3 py-2 font-medium">Ceiling</th>
                                        <th className="px-3 py-2 font-medium">Employee</th>
                                        <th className="px-3 py-2 font-medium">Employer</th>
                                        <th className="px-3 py-2 font-medium">Mode</th>
                                        <th className="px-3 py-2 font-medium">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {history.map((row) => (
                                        <tr key={row.id} className="border-t">
                                            <td className="px-3 py-2">{row.effective_from}</td>
                                            <td className="px-3 py-2">{rupees(row.coverage_ceiling)}</td>
                                            <td className="px-3 py-2">{row.employee_rate}%</td>
                                            <td className="px-3 py-2">{row.employer_rate}%</td>
                                            <td className="px-3 py-2">{row.calculation_mode === 'capped' ? 'Capped (not statutory)' : row.calculation_mode === 'cutoff' ? 'No ESIC above ceiling (not statutory)' : 'Statutory'}</td>
                                            <td className="px-3 py-2">{row.note ?? ''}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Esic.layout = {
    breadcrumbs: [
        {
            title: 'ESIC rules',
            href: edit(),
        },
    ],
};
