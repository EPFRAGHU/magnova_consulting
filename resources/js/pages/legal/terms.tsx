import { usePage } from '@inertiajs/react';
import { LegalPage } from '@/components/legal-page';

export default function Terms() {
    const { appInfo } = usePage().props;

    return (
        <LegalPage title="Terms & Conditions" updated="2 November 2026">
            <p>
                These terms govern the use of the MAGNOVA workspace (the
                &quot;Software&quot;), a manpower attendance, payroll and
                billing application developed and maintained by{' '}
                {appInfo.developer} (the &quot;Developer&quot;) for Magnova
                Consulting Pvt. Ltd. and its authorised users (the
                &quot;Company&quot;). By signing in you agree to these terms.
            </p>

            <h2>1. Authorised use</h2>
            <ul>
                <li>
                    The Software is for the Company&apos;s own business. Only
                    people the Company has authorised may sign in.
                </li>
                <li>
                    Keep your password and one-time codes private. Do not share
                    your sign-in. Everything done under your sign-in is treated
                    as done by you.
                </li>
                <li>
                    Do not try to break into the Software, copy it, reverse
                    engineer it, or use it to harm others or break the law.
                </li>
            </ul>

            <h2>2. Your data and your responsibility</h2>
            <ul>
                <li>
                    The Company owns the information it enters (clients,
                    employees, attendance, rates, wages, invoices) and remains
                    responsible for its accuracy and for having the right to use
                    it.
                </li>
                <li>
                    The Software calculates wages, bonus, EPF, ESIC, service
                    charge, GST and invoice totals from the data and settings
                    entered. These figures are an aid. The Company must check
                    them before paying wages, issuing invoices, or filing
                    returns with the EPFO, ESIC, the GST portal or any
                    authority.
                </li>
                <li>
                    Statutory rates, ceilings and rules change. The Company must
                    keep the settings up to date and confirm them with its own
                    accountant or consultant.
                </li>
            </ul>

            <h2>3. Availability, updates and backups</h2>
            <p>
                We work to keep the Software available and to back up the data
                regularly, but we cannot promise it will always be uninterrupted
                or error free. We may update the Software, show a maintenance
                notice, or pause it briefly for improvements. The Company should
                also keep its own copies of important records, such as issued
                invoices and filed returns.
            </p>

            <h2>4. Ownership of the Software</h2>
            <p>
                The Software, its design, layouts, code and documentation belong
                to {appInfo.developer}. These terms give the Company a right to
                use the Software for its business. They do not transfer
                ownership of it.
            </p>

            <h2>5. Confidentiality and privacy</h2>
            <p>
                Business and personal information in the Software is treated as
                confidential and used only to provide and support the Software.
                Please read our Privacy Policy, which explains what is collected
                and why.
            </p>

            <h2>6. Limits of liability</h2>
            <p>
                To the extent the law allows, the Developer is not liable for
                indirect or consequential loss, such as penalties, interest,
                lost profit or lost business, arising from use of the Software,
                from wrong data entered, from figures used without checking, or
                from delay or interruption of the service. Where liability
                cannot be excluded, it is limited to the amount the Company paid
                for the Software in the twelve months before the claim.
            </p>

            <h2>7. Suspension and ending</h2>
            <p>
                Access may be suspended or ended if these terms are broken or
                the sign-in is misused. The Company may ask for its data to be
                exported or deleted, subject to records the law requires to be
                kept.
            </p>

            <h2>8. Changes to these terms</h2>
            <p>
                We may update these terms. The date at the top shows the latest
                version. Continuing to use the Software after a change means the
                new terms are accepted.
            </p>

            <h2>9. Governing law</h2>
            <p>
                These terms are governed by the laws of India. The courts at
                Bhubaneswar, Odisha have jurisdiction over any dispute.
            </p>

            <h2>10. Contact</h2>
            <p>
                Questions about these terms:{' '}
                <a
                    className="text-blue-700 hover:underline dark:text-blue-300"
                    href={`mailto:${appInfo.contactEmail}`}
                >
                    {appInfo.contactEmail}
                </a>
                .
            </p>
        </LegalPage>
    );
}
