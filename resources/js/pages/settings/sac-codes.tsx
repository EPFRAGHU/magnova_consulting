import { Form, Head } from '@inertiajs/react';
import SacCodeController from '@/actions/App/Http/Controllers/Settings/SacCodeController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/sac-codes';

type Row = { designation: string; sac_code: string };

type Props = { rows: Row[]; default_sac: string };

export default function SacCodes({ rows, default_sac: defaultSac }: Props) {
    return (
        <>
            <Head title="SAC codes" />
            <h1 className="sr-only">SAC codes</h1>
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="SAC codes by designation"
                    description={`Each row of the invoice's Summary of Duties shows the SAC code set here for its designation. A designation left blank uses the default ${defaultSac}. Confirm the codes with your accountant.`}
                />
                {rows.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No designations yet. Add designations to your employees
                        first, and they will appear here.
                    </p>
                ) : (
                    <Form
                        {...SacCodeController.update.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-4">
                                    {rows.map((row, index) => (
                                        <div
                                            className="grid items-start gap-2 sm:grid-cols-[1fr_10rem]"
                                            key={row.designation}
                                        >
                                            <div className="pt-2 text-sm font-medium">
                                                {row.designation}
                                                <input
                                                    type="hidden"
                                                    name={`rows[${index}][designation]`}
                                                    value={row.designation}
                                                />
                                            </div>
                                            <div className="grid gap-1">
                                                <Input
                                                    name={`rows[${index}][sac_code]`}
                                                    defaultValue={row.sac_code}
                                                    placeholder={defaultSac}
                                                    maxLength={6}
                                                    inputMode="numeric"
                                                    autoComplete="off"
                                                    aria-label={`SAC code for ${row.designation}`}
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `rows.${index}.sac_code`
                                                        ]
                                                    }
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>Save</Button>
                                </div>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

SacCodes.layout = {
    breadcrumbs: [{ title: 'SAC codes', href: edit() }],
};
