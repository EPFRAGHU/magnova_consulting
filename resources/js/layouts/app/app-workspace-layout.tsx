import { usePage } from '@inertiajs/react';
import { Building2, Menu } from 'lucide-react';
import { useState } from 'react';
import { MaintenanceBanner } from '@/components/maintenance-banner';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { WorkspaceSidebar } from '@/components/workspace-sidebar';
import { useInitials } from '@/hooks/use-initials';
import type { AppLayoutProps } from '@/types';

export default function AppWorkspaceLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { props, url } = usePage();
    const { auth, loadedClient } = props;
    const path = url.split('?')[0].replace(/\/$/, '');
    // The client whose data these pages show; named in the header so the user always knows whose data they enter.
    const showClient =
        loadedClient &&
        [
            '/clients',
            '/employees',
            '/attendance',
            '/wages',
            '/invoices',
            '/payslips',
        ].includes(path);
    const getInitials = useInitials();
    const [open, setOpen] = useState(false);
    const current = breadcrumbs[breadcrumbs.length - 1]?.title ?? 'Page';

    return (
        <div className="dark:bg-background min-h-screen bg-slate-50">
            {/* Desktop sidebar */}
            <aside className="fixed inset-y-0 left-0 z-20 hidden w-[215px] lg:block xl:w-[244px]">
                <WorkspaceSidebar />
            </aside>

            <div className="flex min-h-screen min-w-0 flex-col lg:ml-[215px] xl:ml-[244px]">
                <header className="sticky top-0 z-30">
                    <MaintenanceBanner inline />
                    <div className="bg-card flex h-[55px] items-center gap-3 border-b px-3.5 lg:h-[62px] lg:px-8">
                        <Sheet open={open} onOpenChange={setOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="lg:hidden"
                                    aria-label="Open menu"
                                >
                                    <Menu className="size-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="w-[250px] border-0 p-0 [&>button]:text-white"
                            >
                                <SheetTitle className="sr-only">
                                    Navigation menu
                                </SheetTitle>
                                <SheetDescription className="sr-only">
                                    Main navigation
                                </SheetDescription>
                                <WorkspaceSidebar
                                    onNavigate={() => setOpen(false)}
                                />
                            </SheetContent>
                        </Sheet>

                        <nav
                            aria-label="Breadcrumb"
                            className="text-muted-foreground text-xs"
                        >
                            Workspace
                            <span className="px-2.5 opacity-50">/</span>
                            <strong className="text-foreground font-semibold">
                                {current}
                            </strong>
                        </nav>

                        {showClient && loadedClient && (
                            <div className="hidden min-w-0 flex-1 justify-center md:flex">
                                <div className="bg-muted/50 flex max-w-xl min-w-0 items-center gap-2.5 rounded-xl border px-3 py-1">
                                    <Building2 className="text-muted-foreground size-4 shrink-0" />
                                    <div className="min-w-0 leading-tight">
                                        <div className="truncate text-sm font-semibold">
                                            {loadedClient.name}
                                            {loadedClient.unit_label &&
                                                ` - ${loadedClient.unit_label}`}
                                        </div>
                                        {loadedClient.address && (
                                            <div
                                                className="text-muted-foreground truncate text-[11px]"
                                                title={loadedClient.address}
                                            >
                                                {loadedClient.address.replace(
                                                    /\s*\n\s*/g,
                                                    ', ',
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="ml-auto flex items-center gap-3">
                            <span
                                className="grid size-[34px] place-items-center rounded-full bg-[#e5efff] text-sm font-bold text-[#175fe2]"
                                title={auth.user?.name}
                            >
                                {getInitials(auth.user?.name ?? '')}
                            </span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-[1650px] flex-1 flex-col">
                    {children}
                </main>
            </div>
        </div>
    );
}
