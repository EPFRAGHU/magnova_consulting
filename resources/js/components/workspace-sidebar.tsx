import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarCheck,
    ChevronsUpDown,
    CircleHelp,
    FileText,
    LayoutGrid,
    Receipt,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as attendanceIndex } from '@/routes/attendance';
import { index as clientsIndex } from '@/routes/clients';
import { index as employeesIndex } from '@/routes/employees';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as payslipsIndex } from '@/routes/payslips';
import { index as wagesIndex } from '@/routes/wages';
import type { NavItem } from '@/types';

// Only screens that exist. Duty Roster and Reports are left out until they are built.
export const workspaceNav: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Clients & Sites', href: clientsIndex(), icon: Building2 },
    { title: 'Guards & Staff', href: employeesIndex(), icon: Users },
    { title: 'Attendance', href: attendanceIndex(), icon: CalendarCheck },
    { title: 'Payroll', href: wagesIndex(), icon: Wallet },
    { title: 'Invoices', href: invoicesIndex(), icon: Receipt },
    { title: 'Payslips', href: payslipsIndex(), icon: FileText },
];

const SUPPORT_EMAIL = 'admin@magnovahr.in';

export function WorkspaceSidebar({ onNavigate }: { onNavigate?: () => void }) {
    const { auth } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const getInitials = useInitials();

    return (
        <div className="flex h-full flex-col bg-gradient-to-b from-[#102b52] to-[#0b2142] px-3.5 pt-[18px] pb-3 text-[#dce7fa]">
            <Link
                href={dashboard()}
                onClick={onNavigate}
                aria-label="Magnova dashboard"
                className="flex h-[62px] items-center gap-2.5 border-b border-white/15 px-2.5 pb-[18px] text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
                <span className="grid size-10 shrink-0 place-items-center rounded-[11px] bg-white p-1">
                    <AppLogoIcon className="size-8" />
                </span>
                <span className="text-[17px] leading-[1.1] font-extrabold tracking-wide">
                    MAGNOVA
                </span>
            </Link>

            <div className="mx-3 mt-6 mb-2.5 text-[10px] font-bold tracking-[1.5px] text-[#8fa4c4]">
                WORKSPACE
            </div>
            <nav aria-label="Main" className="grid gap-[5px]">
                {workspaceNav.map((item) => {
                    const active = isCurrentOrParentUrl(item.href);

                    return (
                        <Link
                            key={item.title}
                            href={item.href}
                            onClick={onNavigate}
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'flex items-center gap-3 rounded-[9px] px-3 py-[11px] text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
                                active
                                    ? 'bg-[#1764ed] text-white shadow-[0_5px_15px_rgba(6,27,61,0.35)]'
                                    : 'text-[#c3d0e6] hover:bg-white/10 hover:text-white',
                            )}
                        >
                            {item.icon && (
                                <item.icon className="size-5 shrink-0" />
                            )}
                            {item.title}
                        </Link>
                    );
                })}
            </nav>

            <div className="mt-auto">
                <div className="mx-0.5 mt-2.5 mb-4 rounded-xl border border-white/15 bg-white/5 p-3">
                    <CircleHelp className="mb-2.5 size-5 text-[#9db4d5]" />
                    <strong className="text-xs text-white">
                        Need assistance?
                    </strong>
                    <p className="mt-1.5 mb-2 text-[11px] leading-normal text-[#aebfda]">
                        Get help with your operations workspace.
                    </p>
                    <a
                        href={`mailto:${SUPPORT_EMAIL}`}
                        className="text-[11px] font-bold text-[#8db8ff] hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Contact support
                    </a>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            aria-label="Account menu"
                            className="flex w-full items-center gap-2.5 rounded-lg border-t border-white/15 px-1.5 pt-3.5 pb-1 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        >
                            <span className="grid size-[34px] shrink-0 place-items-center rounded-full bg-[#e5efff] text-sm font-bold text-[#175fe2]">
                                {getInitials(auth.user?.name ?? '')}
                            </span>
                            <span className="min-w-0 flex-1">
                                <strong className="block truncate text-xs text-white">
                                    {auth.user?.name}
                                </strong>
                                <small className="mt-[3px] block truncate text-[11px] text-[#91a7c9]">
                                    {auth.user?.email}
                                </small>
                            </span>
                            <ChevronsUpDown className="size-4 shrink-0 text-[#a9bad3]" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-56"
                        align="end"
                        side="top"
                    >
                        {auth.user && <UserMenuContent user={auth.user} />}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
