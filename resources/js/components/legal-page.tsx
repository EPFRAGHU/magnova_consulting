import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { dashboard, home, privacy, terms } from '@/routes';

/** A plain, readable page for the legal texts; open to everyone, signed in or not. */
export function LegalPage({
    title,
    updated,
    children,
}: {
    title: string;
    updated: string;
    children: ReactNode;
}) {
    const { appInfo, auth } = usePage().props;

    return (
        <>
            <Head title={title} />
            <div className="bg-background text-foreground min-h-screen">
                <header className="border-b">
                    <div className="mx-auto flex max-w-3xl items-center justify-between px-5 py-4">
                        <Link
                            href={home()}
                            className="text-base font-extrabold tracking-wide"
                        >
                            MAGNOVA
                        </Link>
                        <Link
                            href={auth.user ? dashboard() : home()}
                            className="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300"
                        >
                            {auth.user ? 'Back to dashboard' : 'Back to home'}
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-3xl px-5 py-8">
                    <h1 className="text-2xl font-bold">{title}</h1>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Last updated: {updated}
                    </p>
                    <div className="mt-6 space-y-5 text-sm leading-relaxed [&_h2]:mt-7 [&_h2]:mb-2 [&_h2]:text-base [&_h2]:font-semibold [&_li]:mt-1 [&_ul]:list-disc [&_ul]:pl-5">
                        {children}
                    </div>
                </main>

                <footer className="text-muted-foreground mx-auto max-w-3xl border-t px-5 py-5 text-center text-[11px]">
                    <p>
                        &copy; {new Date().getFullYear()} {appInfo.developer}.
                        All rights reserved.
                    </p>
                    <p className="mt-1 flex items-center justify-center gap-3">
                        <Link href={terms()} className="hover:underline">
                            Terms &amp; Conditions
                        </Link>
                        <span aria-hidden="true">|</span>
                        <Link href={privacy()} className="hover:underline">
                            Privacy Policy
                        </Link>
                    </p>
                </footer>
            </div>
        </>
    );
}
