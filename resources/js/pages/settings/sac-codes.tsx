import { Form, Head } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useMemo, useState } from 'react';
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
};

type Props = {
    rows: Row[];
    default_sac: string;
    default_description: string | null;
    catalog: Record<string, string>;
};

export default function SacCodes({
    rows: initialRows,
    default_sac: defaultSac,
    catalog,
}: Props) {
    const [rows, setRows] = useState<Row[]>(initialRows);
    const [newName, setNewName] = useState('');
    const [search, setSearch] = useState('');
    const [activeIndex, setActiveIndex] = useState<number | null>(null);

    const matches = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (term.length < 2) return [];
        return Object.entries(catalog)
            .filter(
                ([code, description]) =>
                    code.startsWith(term) || description.toLowerCase().includes(term),
            )
            .slice(0, 8);
    }, [search, catalog]);

    const useCode = (code: string, description: string) => {
        if (activeIndex === null || !rows[activeIndex]) return;
        update(activeIndex, { sac_code: code, sac_description: description });
    };

    const update = (index: number, patch: Partial<Row>) =>
        setRows((current) =>
            current.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );

    const changeCode = (index: number, code: string) => {
        const digits = code.replace(/\D/g, '').slice(0, 6);
        const row = rows[index];
        const untouched =
            row.sac_description === '' ||
            row.sac_description === (catalog[row.sac_code] ?? '');

        update(index, {
            sac_code: digits,
            ...(untouched ? { sac_description: catalog[digits] ?? '' } : {}),
        });
    };

    const addRow = () => {
        const name = newName.trim();
        setNewName('');
        if (!name || rows.some((r) => r.designation.toLowerCase() === name.toLowerCase())) {
            return;
        }
        setRows((current) => [
            ...current,
            { designation: name, sac_code: '', sac_description: '', in_use: false },
        ]);
    };

    return (
        <>
            <Head title="SAC codes" />
            <h1 className="sr-only">SAC codes</h1>
            <div className="space-y-4">
                <Heading
                    variant="small"
                    title="Designations and SAC codes"
                    description={`Add a designation and its SAC code. Employees pick from this list, and the code prints on the Tax Invoice. Blank code uses ${defaultSac}.`}
                />

                <Form
                    {...SacCodeController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <InputError message={errors.rows} />

                            <div className="overflow-hidden rounded-md border">
                                <div className="bg-muted/50 text-muted-foreground grid grid-cols-[minmax(0,1.3fr)_5.5rem_minmax(0,2fr)_2rem] items-center gap-2 px-3 py-1.5 text-xs font-medium">
                                    <span>Designation</span>
                                    <span>SAC code</span>
                                    <span>Description</span>
                                    <span />
                                </div>
                                {rows.length === 0 && (
                                    <p className="text-muted-foreground px-3 py-4 text-sm">
                                        No designations yet. Add one below.
                                    </p>
                                )}
                                {rows.map((row, index) => (
                                    <div
                                        key={`${row.designation}-${index}`}
                                        className="grid grid-cols-[minmax(0,1.3fr)_5.5rem_minmax(0,2fr)_2rem] items-start gap-2 border-t px-3 py-1.5"
                                    >
                                        <div className="truncate pt-1.5 text-sm font-medium" title={row.designation}>
                                            {row.designation}
                                            <input
                                                type="hidden"
                                                name={`rows[${index}][designation]`}
                                                value={row.designation}
                                            />
                                        </div>
                                        <div>
                                            <Input
                                                name={`rows[${index}][sac_code]`}
                                                value={row.sac_code}
                                                onChange={(e) => changeCode(index, e.target.value)}
                                                onFocus={() => setActiveIndex(index)}
                                                placeholder={defaultSac}
                                                maxLength={6}
                                                inputMode="numeric"
                                                autoComplete="off"
                                                aria-label={`SAC code for ${row.designation}`}
                                                className="h-8 px-2 text-sm"
                                            />
                                            <InputError message={errors[`rows.${index}.sac_code`]} />
                                        </div>
                                        <div>
                                            <Input
                                                name={`rows[${index}][sac_description]`}
                                                value={row.sac_description}
                                                onChange={(e) =>
                                                    update(index, { sac_description: e.target.value })
                                                }
                                                placeholder="Fills in for known codes"
                                                maxLength={255}
                                                autoComplete="off"
                                                aria-label={`Description for ${row.designation}`}
                                                className="h-8 px-2 text-sm"
                                            />
                                            <InputError message={errors[`rows.${index}.sac_description`]} />
                                        </div>
                                        {row.in_use ? (
                                            <span
                                                className="text-muted-foreground pt-1.5 text-center text-xs"
                                                title="Used by employees, so it cannot be removed"
                                            >
                                                •
                                            </span>
                                        ) : (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                aria-label={`Remove ${row.designation}`}
                                                onClick={() =>
                                                    setRows((current) => current.filter((_, i) => i !== index))
                                                }
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                ))}

                                <div className="bg-muted/30 flex items-center gap-2 border-t px-3 py-2">
                                    <Input
                                        value={newName}
                                        onChange={(e) => setNewName(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                addRow();
                                            }
                                        }}
                                        placeholder="New designation, e.g. Sweeper"
                                        maxLength={255}
                                        autoComplete="off"
                                        aria-label="New designation"
                                        className="h-8 text-sm"
                                    />
                                    <Button type="button" variant="outline" size="sm" onClick={addRow}>
                                        Add
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-1.5 rounded-md border p-3">
                                <label htmlFor="sac-search" className="text-sm font-medium">
                                    Find a SAC code
                                </label>
                                <Input
                                    id="sac-search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Type a word or code, e.g. cleaning, guard, 9985"
                                    autoComplete="off"
                                    className="h-8 text-sm"
                                />
                                {search.trim().length >= 2 && matches.length === 0 && (
                                    <p className="text-muted-foreground text-xs">No match.</p>
                                )}
                                {matches.length > 0 && (
                                    <ul className="divide-y rounded-md border text-sm">
                                        {matches.map(([code, description]) => (
                                            <li key={code}>
                                                <button
                                                    type="button"
                                                    className="hover:bg-muted flex w-full items-baseline gap-2 px-2 py-1 text-left"
                                                    onMouseDown={(e) => e.preventDefault()}
                                                    onClick={() => useCode(code, description)}
                                                >
                                                    <span className="font-mono text-xs">{code}</span>
                                                    <span className="truncate">{description}</span>
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <p className="text-muted-foreground text-xs">
                                    Click a row's SAC code box first, then click a result to fill it in.
                                    The full official list has {Object.keys(catalog).length} codes.
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                <Button disabled={processing}>Save</Button>
                                <span className="text-muted-foreground text-xs">
                                    • = in use by employees
                                </span>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

SacCodes.layout = {
    breadcrumbs: [{ title: 'SAC codes', href: edit() }],
};
