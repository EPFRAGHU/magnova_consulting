import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import SacCodeController from '@/actions/App/Http/Controllers/Settings/SacCodeController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/sac-codes';

type Row = {
    designation: string;
    sac_code: string;
    sac_description: string;
    in_use: boolean;
    isNew?: boolean;
};

type Props = {
    rows: Omit<Row, 'isNew'>[];
    default_sac: string;
    default_description: string | null;
    catalog: Record<string, string>;
};

export default function SacCodes({
    rows: initialRows,
    default_sac: defaultSac,
    default_description: defaultDescription,
    catalog,
}: Props) {
    const [rows, setRows] = useState<Row[]>(initialRows);
    const [newName, setNewName] = useState('');

    const update = (index: number, patch: Partial<Row>) =>
        setRows((current) =>
            current.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );

    const changeCode = (index: number, code: string) => {
        const digits = code.replace(/\D/g, '').slice(0, 6);
        const row = rows[index];
        const previousSuggestion = catalog[row.sac_code] ?? '';
        const suggestion = catalog[digits];
        const descriptionUntouched =
            row.sac_description === '' ||
            row.sac_description === previousSuggestion;

        update(index, {
            sac_code: digits,
            ...(suggestion && descriptionUntouched
                ? { sac_description: suggestion }
                : {}),
            ...(digits === '' && descriptionUntouched
                ? { sac_description: '' }
                : {}),
        });
    };

    const addRow = () => {
        const name = newName.trim();
        if (!name) return;
        if (rows.some((r) => r.designation.toLowerCase() === name.toLowerCase())) {
            setNewName('');
            return;
        }
        setRows((current) => [
            ...current,
            { designation: name, sac_code: '', sac_description: '', in_use: false, isNew: true },
        ]);
        setNewName('');
    };

    return (
        <>
            <Head title="Designations and SAC codes" />
            <h1 className="sr-only">Designations and SAC codes</h1>
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Designations and SAC codes"
                    description={`One list for the whole system. Employees choose their designation from it, and the same SAC code and description then appear on the Employees, Attendance, Wages, Register of Wages, Payslips and Invoices. A designation with no code uses the default ${defaultSac}${defaultDescription ? ` (${defaultDescription})` : ''}. Confirm the codes with your accountant.`}
                />

                <Form
                    {...SacCodeController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <InputError message={errors.rows} />
                            {rows.length === 0 && (
                                <p className="text-muted-foreground text-sm">
                                    No designations yet. Add the first one
                                    below.
                                </p>
                            )}
                            <div className="space-y-5">
                                {rows.map((row, index) => (
                                    <div
                                        className="grid gap-2 rounded-md border p-3 sm:grid-cols-[1fr_8rem]"
                                        key={`${row.designation}-${index}`}
                                    >
                                        <div className="flex items-center justify-between sm:col-span-2">
                                            <span className="text-sm font-semibold">
                                                {row.designation}
                                                {row.in_use && (
                                                    <span className="text-muted-foreground ml-2 text-xs font-normal">
                                                        in use by employees
                                                    </span>
                                                )}
                                            </span>
                                            {!row.in_use && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setRows((current) =>
                                                            current.filter(
                                                                (_, i) => i !== index,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            )}
                                            <input
                                                type="hidden"
                                                name={`rows[${index}][designation]`}
                                                value={row.designation}
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <Input
                                                name={`rows[${index}][sac_description]`}
                                                value={row.sac_description}
                                                onChange={(e) =>
                                                    update(index, {
                                                        sac_description: e.target.value,
                                                    })
                                                }
                                                placeholder="Description (fills in by itself for known codes)"
                                                maxLength={255}
                                                autoComplete="off"
                                                aria-label={`Description for ${row.designation}`}
                                            />
                                            <InputError
                                                message={errors[`rows.${index}.sac_description`]}
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <Input
                                                name={`rows[${index}][sac_code]`}
                                                value={row.sac_code}
                                                onChange={(e) =>
                                                    changeCode(index, e.target.value)
                                                }
                                                placeholder={defaultSac}
                                                maxLength={6}
                                                inputMode="numeric"
                                                autoComplete="off"
                                                aria-label={`SAC code for ${row.designation}`}
                                            />
                                            <InputError
                                                message={errors[`rows.${index}.sac_code`]}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="flex items-end gap-2">
                                <div className="grid flex-1 gap-1">
                                    <label
                                        htmlFor="new-designation"
                                        className="text-sm font-medium"
                                    >
                                        Add a designation
                                    </label>
                                    <Input
                                        id="new-designation"
                                        value={newName}
                                        onChange={(e) => setNewName(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                addRow();
                                            }
                                        }}
                                        placeholder="e.g. Sweeper"
                                        maxLength={255}
                                        autoComplete="off"
                                    />
                                </div>
                                <Button type="button" variant="outline" onClick={addRow}>
                                    Add
                                </Button>
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

SacCodes.layout = {
    breadcrumbs: [{ title: 'Designations and SAC codes', href: edit() }],
};
