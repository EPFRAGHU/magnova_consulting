import { usePage } from '@inertiajs/react';
import { LegalPage } from '@/components/legal-page';

export default function Privacy() {
    const { appInfo } = usePage().props;

    return (
        <LegalPage title="Privacy Policy" updated="2 November 2026">
            <p>
                This policy explains what information the MAGNOVA workspace (the
                &quot;Software&quot;) collects, why, and how it is protected.
                The Software is developed and maintained by {appInfo.developer}{' '}
                for Magnova Consulting Pvt. Ltd. (the &quot;Company&quot;). The
                Company decides what personal information about its employees
                and clients is entered; the Developer handles it on the
                Company&apos;s behalf to run the Software.
            </p>

            <h2>1. Information we handle</h2>
            <ul>
                <li>
                    <strong>Sign-in details:</strong> name, email address,
                    mobile number, a protected (hashed) password, and the
                    one-time codes used to sign in by mobile.
                </li>
                <li>
                    <strong>Employee details:</strong> name, gender, date of
                    joining, designation, employee code, UAN, EPF member ID,
                    ESIC number, bank account details, daily rate, attendance
                    and overtime, wages, bonus and statutory deductions.
                </li>
                <li>
                    <strong>Client and billing details:</strong> client names,
                    addresses, GSTIN, contact persons, contract and order
                    details, invoices and their amounts.
                </li>
                <li>
                    <strong>Technical information:</strong> a session cookie
                    needed to keep you signed in, and server logs used to keep
                    the Software secure and working.
                </li>
            </ul>

            <h2>2. Why we use it</h2>
            <ul>
                <li>To sign you in and keep the account secure.</li>
                <li>
                    To prepare attendance registers, wage registers, payslips,
                    tax invoices and statutory files for the Company.
                </li>
                <li>
                    To support the Company, fix faults and improve the Software.
                </li>
                <li>To meet legal duties, such as record keeping.</li>
            </ul>
            <p>
                We do not sell personal information and we do not use it for
                advertising.
            </p>

            <h2>3. Who else handles it</h2>
            <p>
                Only the service providers needed to run the Software, under the
                same duty to protect it:
            </p>
            <ul>
                <li>
                    a server host in Mumbai, India, where the Software and its
                    database run;
                </li>
                <li>
                    an SMS provider, which receives a mobile number and a
                    one-time code to deliver it;
                </li>
                <li>
                    storage providers for protected backups, including a
                    read-only copy of the database kept for recovery outside the
                    main server (this copy may be held in Singapore).
                </li>
            </ul>
            <p>
                We also share information when the law or a competent authority
                requires it.
            </p>

            <h2>4. How we protect it</h2>
            <ul>
                <li>Connections to the Software are encrypted (HTTPS).</li>
                <li>
                    Passwords and sign-in codes are stored in protected (hashed)
                    form, never as plain text.
                </li>
                <li>
                    Only authorised users can sign in, and the database is not
                    open to the public internet.
                </li>
                <li>The database is backed up regularly.</li>
            </ul>
            <p>
                No system is perfectly secure. If a breach affects personal
                information we will inform the Company without delay.
            </p>

            <h2>5. How long we keep it</h2>
            <p>
                Information is kept while the Company uses the Software and for
                as long as the law requires payroll, tax and billing records to
                be kept. After that it is deleted or made anonymous.
            </p>

            <h2>6. Your rights</h2>
            <p>
                Under the Digital Personal Data Protection Act, 2023 and other
                Indian law, you can ask to see the information held about you,
                to correct it, to have it erased where the law allows, and to
                raise a complaint. Employees should first ask the Company, which
                holds their records. You can also write to us at the address
                below.
            </p>

            <h2>7. Cookies</h2>
            <p>
                The Software uses only the cookies it needs to work, such as the
                one that keeps you signed in. It uses no advertising or tracking
                cookies.
            </p>

            <h2>8. Changes to this policy</h2>
            <p>
                We may update this policy. The date at the top shows the latest
                version.
            </p>

            <h2>9. Contact</h2>
            <p>
                Privacy questions and requests:{' '}
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
