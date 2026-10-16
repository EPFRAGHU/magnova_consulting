import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Menu, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { MaintenanceBanner } from '@/components/maintenance-banner';
import { cn } from '@/lib/utils';
import { dashboard, login } from '@/routes';

const BTN =
    'inline-flex items-center justify-center rounded-[10px] px-[21px] py-[13px] text-sm font-bold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1764ed]';
const BTN_PRIMARY = `${BTN} bg-[#1764ed] text-white shadow-[0_8px_20px_rgba(23,100,237,0.13)] hover:bg-[#0e55d6]`;
const BTN_OUTLINE = `${BTN} border border-[#e4eaf3] bg-white text-[#102b52] hover:bg-[#f4f7fc]`;

const NAV_LINKS = [
    { href: '#features', label: 'Features' },
    { href: '#workflow', label: 'How it works' },
    { href: '#contact', label: 'Contact' },
];

const FEATURES = [
    {
        icon: '▦',
        title: 'Clients & sites',
        text: 'Organise client details, service locations, deployment requirements and site-level records.',
    },
    {
        icon: '♙',
        title: 'Workforce management',
        text: 'Maintain employee profiles, assignments, job roles and essential workforce information.',
    },
    {
        icon: '◷',
        title: 'Attendance & shifts',
        text: 'Keep attendance and shift information structured for review and monthly processing.',
    },
    {
        icon: '₹',
        title: 'Payroll & billing',
        text: 'Bring wage records, payslips, invoices and reports into a consistent workflow.',
    },
];

const STEPS = [
    {
        tag: '01 / ORGANISE',
        title: 'Set up clients and teams',
        text: 'Maintain client, site and employee information in one structured workspace.',
    },
    {
        tag: '02 / RECORD',
        title: 'Track daily operations',
        text: 'Record attendance, shifts and deployment details to support regular review.',
    },
    {
        tag: '03 / REVIEW',
        title: 'Prepare monthly outputs',
        text: 'Use connected records to support payroll, billing and management reporting.',
    },
];

const BARS = [42, 62, 51, 78, 64, 91, 72, 98, 80, 88];

function Brand({ onDark = false }: { onDark?: boolean }) {
    return (
        <Link
            href="/"
            className={cn(
                'flex items-center gap-3 font-extrabold tracking-[0.06em]',
                onDark ? 'text-white' : 'text-[#102b52]',
            )}
        >
            <span className="grid size-[42px] place-items-center rounded-xl border border-[#e4eaf3] bg-white p-1">
                <AppLogoIcon className="size-9" />
            </span>
            <span className="text-[13px] leading-tight">
                MAGNOVA
                <br />
                SECURITY
            </span>
        </Link>
    );
}

export default function Welcome() {
    const { auth } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const signedIn = Boolean(auth.user);
    const accessHref = signedIn ? dashboard() : login();

    useEffect(() => {
        if (!menuOpen) return;
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setMenuOpen(false);
        };
        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [menuOpen]);

    return (
        <>
            <Head title="Security operations management">
                <meta
                    name="description"
                    content="Manage security teams, client sites, attendance, payroll and billing from one connected workspace."
                />
            </Head>
            <div className="bg-white text-[#182943]">
                <MaintenanceBanner />

                <header className="relative mx-auto flex h-[72px] w-[min(1160px,calc(100%-30px))] items-center justify-between gap-6 sm:h-[82px] sm:w-[min(1160px,calc(100%-44px))]">
                    <Brand />
                    <nav
                        aria-label="Main"
                        className="flex items-center gap-3 text-sm text-[#52647d] sm:gap-8"
                    >
                        <div className="hidden items-center gap-8 md:flex">
                            {NAV_LINKS.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    className="hover:text-[#1764ed]"
                                >
                                    {link.label}
                                </a>
                            ))}
                        </div>
                        <Link href={accessHref} className={BTN_OUTLINE}>
                            {signedIn ? 'Dashboard' : 'Sign in'}
                        </Link>
                        <button
                            type="button"
                            className="grid size-10 place-items-center rounded-[10px] border border-[#e4eaf3] md:hidden"
                            aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                            aria-expanded={menuOpen}
                            aria-controls="mobile-menu"
                            onClick={() => setMenuOpen((open) => !open)}
                        >
                            {menuOpen ? (
                                <X className="size-5" />
                            ) : (
                                <Menu className="size-5" />
                            )}
                        </button>
                    </nav>
                    {menuOpen && (
                        <div
                            id="mobile-menu"
                            className="absolute inset-x-0 top-full z-30 rounded-2xl border border-[#e4eaf3] bg-white p-2 shadow-[0_20px_50px_rgba(25,57,104,0.12)] md:hidden"
                        >
                            {NAV_LINKS.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setMenuOpen(false)}
                                    className="block rounded-lg px-4 py-3 text-sm font-semibold text-[#182943] hover:bg-[#f4f7fc]"
                                >
                                    {link.label}
                                </a>
                            ))}
                        </div>
                    )}
                </header>

                <main>
                    <section className="overflow-hidden border-t border-[#f0f3f8] bg-gradient-to-br from-[#f5f8ff] via-white to-white py-[42px] sm:pt-[55px] sm:pb-[70px] lg:pt-[76px]">
                        <div className="mx-auto grid w-[min(1160px,calc(100%-30px))] items-center gap-8 sm:w-[min(1160px,calc(100%-44px))] lg:grid-cols-[1.02fr_.98fr] lg:gap-[58px]">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-[#eaf1ff] px-3 py-2 text-xs font-bold tracking-[0.04em] text-[#1856bc]">
                                    <i className="size-[7px] rounded-full bg-[#15966b]" />
                                    SECURITY OPERATIONS MANAGEMENT
                                </div>
                                <h1 className="mt-[22px] mb-5 max-w-[620px] text-[clamp(42px,5.1vw,64px)] leading-[1.06] font-extrabold tracking-[-0.045em] text-[#0b2142]">
                                    People protected.
                                    <br />
                                    <span className="text-[#1764ed]">
                                        Operations under control.
                                    </span>
                                </h1>
                                <p className="max-w-[550px] text-base leading-[1.75] text-[#61718a] sm:text-lg">
                                    A smarter way to manage security teams,
                                    client sites, attendance, payroll and
                                    billing — all from one clear, connected
                                    workspace.
                                </p>
                                <div className="mt-7 flex flex-wrap gap-3">
                                    <Link
                                        href={accessHref}
                                        className={BTN_PRIMARY}
                                    >
                                        {signedIn
                                            ? 'Go to your dashboard'
                                            : 'Access your workspace'}
                                        <ArrowRight className="ml-2 size-4" />
                                    </Link>
                                    <a href="#features" className={BTN_OUTLINE}>
                                        Explore the platform
                                    </a>
                                </div>
                                <ul className="mt-7 flex flex-wrap gap-x-[18px] gap-y-2 text-xs text-[#60718a]">
                                    {[
                                        'Workforce visibility',
                                        'Organised operations',
                                        'One connected system',
                                    ].map((item) => (
                                        <li key={item}>
                                            <span className="mr-[7px] font-black text-[#15966b]">
                                                ✓
                                            </span>
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div
                                className="relative mx-auto w-full max-w-[620px] p-1.5 sm:p-[18px]"
                                role="img"
                                aria-label="Illustration of an operations overview with sample figures"
                            >
                                <div className="absolute inset-[4%_0_0_8%] rotate-3 rounded-[40px] bg-[#dce8ff]" />
                                <div className="relative rounded-[22px] border border-[#e6ebf4] bg-white p-3.5 shadow-[0_24px_70px_rgba(25,57,104,0.1)] sm:p-[22px]">
                                    <div className="mb-5 flex items-center justify-between">
                                        <div className="text-sm font-extrabold text-[#102b52]">
                                            Operations overview
                                        </div>
                                        <div className="rounded-lg bg-[#e8f8f1] px-[9px] py-[7px] text-[11px] font-extrabold text-[#15966b]">
                                            ● SAMPLE PREVIEW
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2.5">
                                        {[
                                            ['Active workforce', '248', 'Example figure'],
                                            ['Client sites', '18', 'Example figure'],
                                            ['Attendance', '96.8%', 'Example figure'],
                                        ].map(([label, value, note]) => (
                                            <div
                                                key={label}
                                                className="rounded-[13px] border border-[#edf1f7] bg-[#f7f9fd] px-2 py-2.5 sm:px-3 sm:py-3.5"
                                            >
                                                <small className="mb-2 block text-[10px] text-[#718097]">
                                                    {label}
                                                </small>
                                                <strong className="text-[19px] text-[#102b52] sm:text-[23px]">
                                                    {value}
                                                </strong>
                                                <em className="mt-[5px] block text-[10px] text-[#15966b] not-italic">
                                                    {note}
                                                </em>
                                            </div>
                                        ))}
                                    </div>
                                    <div className="mt-3.5 rounded-[14px] border border-[#edf1f7] p-4">
                                        <div className="flex justify-between text-xs font-bold">
                                            <span>Workforce activity</span>
                                            <span className="font-medium text-[#8290a5]">
                                                Weekly view
                                            </span>
                                        </div>
                                        <div className="flex h-[106px] items-end gap-[9px] px-2 pt-3.5">
                                            {BARS.map((height, i) => (
                                                <span
                                                    key={i}
                                                    className="min-h-5 flex-1 rounded-t-[5px] rounded-b-[2px] bg-gradient-to-b from-[#6da0ff] to-[#1764ed]"
                                                    style={{ height: `${height}%` }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                    {[
                                        {
                                            icon: '✓',
                                            tone: 'bg-[#eaf8f2] text-[#15966b]',
                                            title: 'Attendance records',
                                            text: 'Centralised shift and site records',
                                            tag: 'Organised',
                                            tagTone: 'text-[#15966b]',
                                        },
                                        {
                                            icon: '₹',
                                            tone: 'bg-[#edf3ff] text-[#1764ed]',
                                            title: 'Payroll & billing',
                                            text: 'Keep monthly records connected',
                                            tag: 'Linked',
                                            tagTone: 'text-[#1764ed]',
                                        },
                                    ].map((row) => (
                                        <div
                                            key={row.title}
                                            className="mt-3 flex items-center gap-3 rounded-xl border border-[#edf1f7] p-3 text-xs"
                                        >
                                            <div
                                                className={cn(
                                                    'grid size-[34px] shrink-0 place-items-center rounded-[10px] font-black',
                                                    row.tone,
                                                )}
                                            >
                                                {row.icon}
                                            </div>
                                            <div>
                                                <b className="mb-1 block text-[#102b52]">
                                                    {row.title}
                                                </b>
                                                <small className="text-[#748198]">
                                                    {row.text}
                                                </small>
                                            </div>
                                            <div
                                                className={cn(
                                                    'ml-auto text-[11px] font-extrabold',
                                                    row.tagTone,
                                                )}
                                            >
                                                {row.tag}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="features" className="scroll-mt-4 py-[54px] sm:py-[74px]">
                        <div className="mx-auto w-[min(1160px,calc(100%-30px))] sm:w-[min(1160px,calc(100%-44px))]">
                            <div className="mx-auto mb-9 max-w-[700px] text-center">
                                <div className="text-xs font-extrabold tracking-[0.12em] text-[#1764ed] uppercase">
                                    One connected workspace
                                </div>
                                <h2 className="my-3 text-[clamp(28px,3.5vw,40px)] font-bold tracking-[-0.035em] text-[#0b2142]">
                                    Built for the realities of security
                                    operations
                                </h2>
                                <p className="leading-[1.7] text-[#6c7b91]">
                                    Bring everyday administration into one
                                    organised place, helping your team spend
                                    less time chasing records and more time
                                    managing service delivery.
                                </p>
                            </div>
                            <div className="grid gap-[17px] sm:grid-cols-2 lg:grid-cols-4">
                                {FEATURES.map((feature) => (
                                    <article
                                        key={feature.title}
                                        className="rounded-2xl border border-[#e4eaf3] bg-white p-6"
                                    >
                                        <div className="grid size-[45px] place-items-center rounded-[13px] bg-[#edf3ff] text-xl font-extrabold text-[#1764ed]">
                                            {feature.icon}
                                        </div>
                                        <h3 className="mt-[18px] mb-[9px] text-base font-bold text-[#102b52]">
                                            {feature.title}
                                        </h3>
                                        <p className="text-[13px] leading-[1.7] text-[#6c7b91]">
                                            {feature.text}
                                        </p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="workflow" className="scroll-mt-4 bg-[#f4f7fc] py-[54px] sm:py-[74px]">
                        <div className="mx-auto w-[min(1160px,calc(100%-30px))] sm:w-[min(1160px,calc(100%-44px))]">
                            <div className="mx-auto mb-9 max-w-[700px] text-center">
                                <div className="text-xs font-extrabold tracking-[0.12em] text-[#1764ed] uppercase">
                                    A clearer workflow
                                </div>
                                <h2 className="my-3 text-[clamp(28px,3.5vw,40px)] font-bold tracking-[-0.035em] text-[#0b2142]">
                                    From deployment to monthly reporting
                                </h2>
                                <p className="leading-[1.7] text-[#6c7b91]">
                                    Keep connected records through the key
                                    stages of your operations.
                                </p>
                            </div>
                            <div className="grid gap-5 md:grid-cols-3">
                                {STEPS.map((step) => (
                                    <article
                                        key={step.tag}
                                        className="rounded-2xl border border-[#e4eaf3] bg-white p-6"
                                    >
                                        <b className="text-xs tracking-[0.1em] text-[#1764ed]">
                                            {step.tag}
                                        </b>
                                        <h3 className="mt-3 mb-2 font-bold text-[#102b52]">
                                            {step.title}
                                        </h3>
                                        <p className="text-sm leading-[1.7] text-[#6c7b91]">
                                            {step.text}
                                        </p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="contact" className="scroll-mt-4 py-[58px]">
                        <div className="mx-auto w-[min(1160px,calc(100%-30px))] sm:w-[min(1160px,calc(100%-44px))]">
                            <div className="flex flex-col items-start justify-between gap-6 rounded-3xl bg-[#0b2142] p-7 text-white sm:flex-row sm:items-center sm:p-[42px]">
                                <div>
                                    <h2 className="mb-[9px] text-[26px] font-bold tracking-[-0.03em] sm:text-3xl">
                                        Bring your operations into focus.
                                    </h2>
                                    <p className="text-[#c4d1e5]">
                                        {signedIn
                                            ? 'Continue to your Magnova Security workspace.'
                                            : 'Sign in to continue to your Magnova Security workspace.'}
                                    </p>
                                </div>
                                <Link
                                    href={accessHref}
                                    className={cn(
                                        BTN,
                                        'bg-white text-[#102b52] hover:bg-[#eaf1ff]',
                                    )}
                                >
                                    {signedIn
                                        ? 'Go to dashboard'
                                        : 'Sign in to Magnova'}
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-[#e4eaf3] py-[25px] text-xs text-[#78869a]">
                    <div className="mx-auto flex w-[min(1160px,calc(100%-30px))] flex-wrap justify-between gap-[18px] sm:w-[min(1160px,calc(100%-44px))]">
                        <span>
                            <strong className="text-[#102b52]">
                                MAGNOVA SECURITY
                            </strong>{' '}
                            · Operations management
                        </span>
                        <span>
                            © {new Date().getFullYear()} Magnova Consulting
                            Pvt. Ltd. All rights reserved.
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
