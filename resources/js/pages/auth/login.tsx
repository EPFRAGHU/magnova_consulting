import { Form, Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { request as requestOtp, resend as resendOtp, verify as verifyOtp } from '@/routes/login/otp';

type Props = {
    status?: string;
    canResetPassword: boolean;
    otpStatus?: string;
    otpChallenge?: { mobile: string } | null;
};

export default function Login({ status, canResetPassword, otpStatus, otpChallenge }: Props) {
    const [otpMode, setOtpMode] = useState(Boolean(otpChallenge));

    useEffect(() => {
        setOtpMode(Boolean(otpChallenge));
    }, [otpChallenge]);

    return (
        <>
            <Head title="Log in" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Forgot your password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            <div className="my-6 flex items-center gap-3 text-sm text-muted-foreground">
                <div className="h-px flex-1 bg-border" />
                <span>or</span>
                <div className="h-px flex-1 bg-border" />
            </div>

            {!otpMode ? (
                <Form
                    {...requestOtp.form()}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="otp_mobile_number">Login with OTP</Label>
                                <Input
                                    id="otp_mobile_number"
                                    type="tel"
                                    name="mobile_number"
                                    required
                                    autoComplete="tel"
                                    placeholder="+91 9876543210"
                                />
                                <InputError message={errors.mobile_number} />
                            </div>
                            <Button type="submit" variant="outline" disabled={processing}>
                                {processing && <Spinner />}
                                Send OTP
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <div className="flex flex-col gap-4">
                    <p className="text-sm text-muted-foreground">
                        Enter OTP sent to {otpChallenge?.mobile ?? 'your mobile number'}.
                    </p>
                    <Form {...verifyOtp.form()} className="flex flex-col gap-4">
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="otp_code">Enter OTP</Label>
                                    <Input
                                        id="otp_code"
                                        name="code"
                                        inputMode="numeric"
                                        pattern="[0-9]*"
                                        maxLength={6}
                                        required
                                        autoFocus
                                        autoComplete="one-time-code"
                                    />
                                    <InputError message={errors.code} />
                                </div>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Verify &amp; Login
                                </Button>
                            </>
                        )}
                    </Form>
                    <Form {...resendOtp.form()} className="text-center">
                        {({ processing }) => (
                            <button type="submit" className="text-sm underline" disabled={processing}>
                                Resend OTP
                            </button>
                        )}
                    </Form>
                    <button type="button" className="text-sm text-muted-foreground underline" onClick={() => setOtpMode(false)}>
                        Change mobile number
                    </button>
                </div>
            )}

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
            {otpStatus && (
                <div className="mt-4 text-center text-sm font-medium text-green-600">
                    {otpStatus}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
