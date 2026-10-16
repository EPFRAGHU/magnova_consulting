import { Form, Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, Lock } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { MaintenanceBanner } from '@/components/maintenance-banner';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { store } from '@/routes/login';
import {
    request as requestOtp,
    resend as resendOtp,
    verify as verifyOtp,
} from '@/routes/login/otp';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    otpStatus?: string;
    otpChallenge?: { mobile: string } | null;
};

const INPUT =
    'w-full rounded-[10px] border border-[#dce4ef] bg-white px-3.5 py-[13px] text-sm text-[#182943] outline-none placeholder:text-[#9aa7b8] focus:border-[#1764ed] focus:shadow-[0_0_0_3px_rgba(23,100,237,0.09)] aria-invalid:border-red-400';
const LABEL = 'mb-2 block text-[13px] font-bold text-[#263954]';
const SUBMIT =
    'flex w-full items-center justify-center gap-2 rounded-[10px] bg-[#1764ed] p-3.5 font-extrabold text-white shadow-[0_9px_20px_rgba(23,100,237,0.15)] hover:bg-[#0e55d6] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1764ed] disabled:opacity-70';
const LINK =
    'font-bold text-[#1764ed] hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1764ed]';

function FieldError({ id, message }: { id: string; message?: string }) {
    if (!message) return null;

    return (
        <p
            id={id}
            role="alert"
            className="mt-2 rounded-[9px] border border-[#f4d6a7] bg-[#fff4e5] px-3 py-2.5 text-xs leading-normal text-[#8c5410]"
        >
            {message}
        </p>
    );
}

function Brand({ dark }: { dark?: boolean }) {
    return (
        <Link
            href={home()}
            className={`flex items-center gap-3 text-[13px] font-extrabold tracking-[0.06em] ${dark ? 'text-white' : 'text-[#102b52]'}`}
        >
            <span className="grid size-[42px] place-items-center rounded-xl bg-white p-1 ring-1 ring-[#e4eaf3]">
                <AppLogoIcon className="size-9" />
            </span>
            <span className="text-[17px] leading-tight">MAGNOVA</span>
        </Link>
    );
}

export default function Login({
    status,
    canResetPassword,
    otpStatus,
    otpChallenge,
}: Props) {
    const [otpMode, setOtpMode] = useState(Boolean(otpChallenge));
    const [showPassword, setShowPassword] = useState(false);
    const [throttled, setThrottled] = useState<string | null>(null);

    // Laravel's rate limiter answers 429 with a plain page; show it as a normal message instead.
    useEffect(
        () =>
            router.on('httpException', (event) => {
                const { response } = event.detail;
                if (response.status !== 429) return;

                event.preventDefault();
                const seconds = Number(response.headers['retry-after']);
                setThrottled(
                    seconds > 0
                        ? `Too many sign-in attempts. Please wait ${seconds} second${seconds === 1 ? '' : 's'} and try again.`
                        : 'Too many sign-in attempts. Please wait a minute and try again.',
                );
            }),
        [],
    );

    useEffect(() => {
        setOtpMode(Boolean(otpChallenge));
    }, [otpChallenge]);

    return (
        <>
            <Head title="Sign in" />
            <MaintenanceBanner />
            <main className="grid min-h-screen bg-[#f4f7fc] text-[#182943] md:grid-cols-2">
                <section className="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-[#102b52] to-[#091b37] px-[clamp(28px,6vw,90px)] py-[42px] text-white md:flex">
                    <span className="pointer-events-none absolute top-[15%] -right-60 size-[560px] rounded-full border border-white/[0.07]" />
                    <span className="pointer-events-none absolute top-[27%] -right-[155px] size-[390px] rounded-full border border-white/[0.07]" />
                    <div className="relative z-10">
                        <Brand dark />
                    </div>
                    <div className="relative z-10 max-w-[530px] py-[55px]">
                        <div className="text-xs font-extrabold tracking-[0.15em] text-[#9fc0ff] uppercase">
                            Your operations workspace
                        </div>
                        <h1 className="my-[18px] text-[clamp(34px,4.2vw,54px)] leading-[1.1] font-bold tracking-[-0.04em]">
                            Everything in place.
                            <br />
                            Every team connected.
                        </h1>
                        <p className="max-w-[440px] text-base leading-[1.8] text-[#c2d0e5]">
                            Access your central workspace for client sites,
                            workforce records, attendance, payroll and billing.
                        </p>
                        <ul className="mt-8 grid gap-4">
                            {[
                                'Keep client and site information organised',
                                'Manage workforce and attendance records',
                                'Support connected payroll and billing workflows',
                            ].map((item) => (
                                <li
                                    key={item}
                                    className="flex items-center gap-3 text-sm text-[#e1e9f6]"
                                >
                                    <span className="grid size-[26px] place-items-center rounded-lg border border-white/20 text-[#7be0b6]">
                                        <Check className="size-4" />
                                    </span>
                                    {item}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="relative z-10 text-xs text-[#9eafc8]">
                        A connected workspace for Magnova operations.
                    </div>
                </section>

                <section className="grid place-items-center px-4 py-6 md:px-[22px] md:py-[35px]">
                    <div className="w-[min(430px,100%)] rounded-[22px] border border-[#e8edf5] bg-white p-6 shadow-[0_12px_40px_rgba(16,43,82,0.04)] sm:p-[clamp(26px,4vw,42px)]">
                        <div className="mb-8 md:hidden">
                            <Brand />
                        </div>
                        <div>
                            <div className="text-xs font-extrabold tracking-[0.1em] text-[#1764ed]">
                                WELCOME BACK
                            </div>
                            <h2 className="mt-2.5 mb-2.5 text-[28px] font-bold tracking-[-0.04em] text-[#0b2142] sm:text-[31px]">
                                Sign in to your account
                            </h2>
                            <p className="mb-[27px] text-sm leading-relaxed text-[#718097]">
                                Enter your account details to continue to your
                                workspace.
                            </p>
                        </div>

                        {status && (
                            <div
                                role="status"
                                className="mb-4 rounded-[9px] border border-[#c9f2d6] bg-[#ecfbf1] px-3 py-2.5 text-xs text-[#137744]"
                            >
                                {status}
                            </div>
                        )}

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            resetOnError={['password']}
                            onBefore={() => {
                                setThrottled(null);
                            }}
                            onError={() => setShowPassword(false)}
                            disableWhileProcessing
                        >
                            {({ processing, errors }) => (
                                <>
                                    {throttled && (
                                        <div className="mb-4">
                                            <FieldError
                                                id="throttle-error"
                                                message={throttled}
                                            />
                                        </div>
                                    )}
                                    <div className="my-[18px] mt-0">
                                        <label htmlFor="email" className={LABEL}>
                                            Email address
                                        </label>
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                            autoFocus
                                            autoComplete="username"
                                            placeholder="Enter your email address"
                                            className={INPUT}
                                            aria-invalid={Boolean(errors.email)}
                                            aria-describedby={
                                                errors.email
                                                    ? 'email-error'
                                                    : undefined
                                            }
                                        />
                                        <FieldError
                                            id="email-error"
                                            message={errors.email}
                                        />
                                    </div>

                                    <div className="my-[18px]">
                                        <label
                                            htmlFor="password"
                                            className={LABEL}
                                        >
                                            Password
                                        </label>
                                        <div className="relative">
                                            <input
                                                id="password"
                                                name="password"
                                                type={
                                                    showPassword
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                required
                                                autoComplete="current-password"
                                                placeholder="Enter your password"
                                                className={`${INPUT} pr-[66px]`}
                                                aria-invalid={Boolean(
                                                    errors.password,
                                                )}
                                                aria-describedby={
                                                    errors.password
                                                        ? 'password-error'
                                                        : undefined
                                                }
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setShowPassword((s) => !s)
                                                }
                                                aria-pressed={showPassword}
                                                className="absolute top-1/2 right-2.5 -translate-y-1/2 text-xs font-bold text-[#1764ed]"
                                            >
                                                {showPassword ? 'Hide' : 'Show'}
                                            </button>
                                        </div>
                                        <FieldError
                                            id="password-error"
                                            message={errors.password}
                                        />
                                    </div>

                                    <div className="mt-[18px] mb-[22px] flex items-center justify-between gap-2.5 text-xs">
                                        <label
                                            htmlFor="remember"
                                            className="flex items-center gap-[7px] text-[#5e6d83]"
                                        >
                                            <input
                                                id="remember"
                                                name="remember"
                                                type="checkbox"
                                                className="accent-[#1764ed]"
                                            />
                                            Remember me
                                        </label>
                                        {canResetPassword && (
                                            <Link
                                                href={request()}
                                                className={LINK}
                                            >
                                                Forgot password?
                                            </Link>
                                        )}
                                    </div>

                                    <button
                                        type="submit"
                                        className={SUBMIT}
                                        disabled={processing}
                                        data-test="login-button"
                                    >
                                        {processing && <Spinner />}
                                        Sign in
                                        {!processing && <span aria-hidden>→</span>}
                                    </button>
                                </>
                            )}
                        </Form>

                        <div className="my-6 flex items-center gap-3 text-xs text-[#8a96a8]">
                            <div className="h-px flex-1 bg-[#e8edf5]" />
                            <span>or</span>
                            <div className="h-px flex-1 bg-[#e8edf5]" />
                        </div>

                        {!otpMode ? (
                            <Form
                                {...requestOtp.form()}
                                disableWhileProcessing
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <label
                                            htmlFor="otp_mobile_number"
                                            className={LABEL}
                                        >
                                            Sign in with a mobile OTP
                                        </label>
                                        <input
                                            id="otp_mobile_number"
                                            name="mobile_number"
                                            type="tel"
                                            required
                                            autoComplete="tel"
                                            placeholder="+91 9876543210"
                                            className={INPUT}
                                            aria-invalid={Boolean(
                                                errors.mobile_number,
                                            )}
                                        />
                                        <FieldError
                                            id="otp-mobile-error"
                                            message={errors.mobile_number}
                                        />
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-[10px] border border-[#dce4ef] bg-white p-3 text-sm font-bold text-[#102b52] hover:bg-[#f4f7fc] disabled:opacity-70"
                                        >
                                            {processing && <Spinner />}
                                            Send OTP
                                        </button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <div className="flex flex-col gap-3">
                                <p className="text-sm text-[#718097]">
                                    Enter the OTP sent to{' '}
                                    {otpChallenge?.mobile ??
                                        'your mobile number'}
                                    .
                                </p>
                                <Form
                                    {...verifyOtp.form()}
                                    disableWhileProcessing
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <label
                                                htmlFor="otp_code"
                                                className={LABEL}
                                            >
                                                Enter OTP
                                            </label>
                                            <input
                                                id="otp_code"
                                                name="code"
                                                inputMode="numeric"
                                                pattern="[0-9]*"
                                                maxLength={6}
                                                required
                                                autoFocus
                                                autoComplete="one-time-code"
                                                className={INPUT}
                                                aria-invalid={Boolean(
                                                    errors.code,
                                                )}
                                            />
                                            <FieldError
                                                id="otp-code-error"
                                                message={errors.code}
                                            />
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className={`${SUBMIT} mt-3`}
                                            >
                                                {processing && <Spinner />}
                                                Verify &amp; sign in
                                            </button>
                                        </>
                                    )}
                                </Form>
                                <Form {...resendOtp.form()} className="text-center">
                                    {({ processing }) => (
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className={`${LINK} text-xs`}
                                        >
                                            Resend OTP
                                        </button>
                                    )}
                                </Form>
                                <button
                                    type="button"
                                    onClick={() => setOtpMode(false)}
                                    className="text-xs text-[#718097] underline"
                                >
                                    Change mobile number
                                </button>
                            </div>
                        )}

                        {otpStatus && (
                            <div
                                role="status"
                                className="mt-4 rounded-[9px] border border-[#c9f2d6] bg-[#ecfbf1] px-3 py-2.5 text-xs text-[#137744]"
                            >
                                {otpStatus}
                            </div>
                        )}

                        <div className="mt-[23px] text-center text-xs leading-[1.8] text-[#7a879a]">
                            Need access or help signing in?
                            <br />
                            Contact your system administrator.
                        </div>
                        <div className="text-center">
                            <Link
                                href={home()}
                                className="mt-[22px] inline-flex items-center gap-1.5 text-[13px] text-[#6b7b91] hover:text-[#1764ed]"
                            >
                                <ArrowLeft className="size-3.5" />
                                Back to home
                            </Link>
                        </div>
                        <div className="mt-5 flex items-center justify-center gap-[7px] text-[11px] text-[#8a96a8]">
                            <Lock className="size-3.5 text-[#15966b]" />
                            Secure sign-in
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
