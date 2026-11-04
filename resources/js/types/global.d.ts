import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            maintenance: string | null;
            loadedClient: {
                id: number;
                name: string;
                unit_label: string | null;
                address: string | null;
            } | null;
            appInfo: {
                version: string;
                versionDate: string;
                startedAt: string;
                developer: string;
                contactEmail: string;
            };
            [key: string]: unknown;
        };
    }
}
