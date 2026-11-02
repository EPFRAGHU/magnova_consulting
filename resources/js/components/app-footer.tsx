import { Link, usePage } from '@inertiajs/react';
import { privacy, terms } from '@/routes';

/** Credit, rights and legal links shown at the bottom of the dashboard. */
export function AppFooter() {
    const { appInfo } = usePage().props;

    return (
        <footer className="text-muted-foreground mt-2 border-t pt-4 pb-2 text-center text-[11px] leading-relaxed">
            <p>
                &copy; {new Date().getFullYear()} {appInfo.developer}. All
                rights reserved.
            </p>
            <p>Developed by {appInfo.developer}</p>
            <p className="mt-1.5 flex items-center justify-center gap-3">
                <Link
                    href={terms()}
                    className="hover:text-foreground underline-offset-2 hover:underline"
                >
                    Terms &amp; Conditions
                </Link>
                <span aria-hidden="true">|</span>
                <Link
                    href={privacy()}
                    className="hover:text-foreground underline-offset-2 hover:underline"
                >
                    Privacy Policy
                </Link>
            </p>
        </footer>
    );
}
