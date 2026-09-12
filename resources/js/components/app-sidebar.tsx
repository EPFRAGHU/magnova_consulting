import { Link } from '@inertiajs/react';
import {
    Building2,
    CalendarCheck,
    FileSpreadsheet,
    FileText,
    LayoutGrid,
    Receipt,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as clientsIndex } from '@/routes/clients';
import { index as employeesIndex } from '@/routes/employees';
import { index as attendanceIndex } from '@/routes/attendance';
import { index as wagesIndex } from '@/routes/wages';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as payslipsIndex } from '@/routes/payslips';
import { index as ecrIndex } from '@/routes/ecr';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Clients',
        href: clientsIndex(),
        icon: Building2,
    },
    {
        title: 'Employees',
        href: employeesIndex(),
        icon: Users,
    },
    {
        title: 'Attendance',
        href: attendanceIndex(),
        icon: CalendarCheck,
    },
    {
        title: 'Wages',
        href: wagesIndex(),
        icon: Wallet,
    },
    {
        title: 'Invoices',
        href: invoicesIndex(),
        icon: Receipt,
    },
    {
        title: 'Payslips',
        href: payslipsIndex(),
        icon: FileText,
    },
    {
        title: 'ECR Export',
        href: ecrIndex(),
        icon: FileSpreadsheet,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
