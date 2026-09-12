import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Magnova" />
            <div className="bg-background flex min-h-screen flex-col items-center justify-center gap-6 p-6 text-center">
                <AppLogoIcon className="h-24" />
                <div className="space-y-2">
                    <h1 className="font-serif text-2xl font-semibold tracking-tight">
                        Magnova Consulting
                    </h1>
                    <p className="text-muted-foreground">
                        Manpower billing, payroll &amp; EPF compliance, in one
                        place.
                    </p>
                </div>
                <Button asChild size="lg">
                    <Link href={auth.user ? dashboard() : login()}>
                        {auth.user ? 'Go to Dashboard' : 'Log in'}
                    </Link>
                </Button>
            </div>
        </>
    );
}
