import { Form, Head } from '@inertiajs/react';
import MaintenanceController from '@/actions/App/Http/Controllers/Settings/MaintenanceController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export default function Maintenance({
    active,
    message,
}: {
    active: boolean;
    message: string;
}) {
    return (
        <>
            <Head title="Maintenance banner" />
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Maintenance banner"
                    description="Switch this on before you update the app. A scrolling warning shows on every page for everyone. Nobody is blocked, the app keeps working."
                />

                <Form
                    {...MaintenanceController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="active"
                                        name="active"
                                        defaultChecked={active}
                                    />
                                    <Label htmlFor="active">
                                        Show the maintenance banner
                                    </Label>
                                </div>
                                <InputError message={errors.active} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="message">Message</Label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    defaultValue={message}
                                    rows={3}
                                    maxLength={300}
                                />
                                <InputError message={errors.message} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
