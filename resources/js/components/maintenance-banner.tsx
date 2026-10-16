import { usePage } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';

// Warns on every page while the app is being updated. It never blocks clicks or navigation.
export function MaintenanceBanner() {
    const { maintenance } = usePage().props;

    if (!maintenance) return null;

    // Each group repeats the message so it is wider than the screen; two identical
    // groups slide left by exactly half the track, which makes the loop seamless.
    const group = (hidden: boolean) => (
        <div aria-hidden={hidden || undefined} className="flex shrink-0">
            {[0, 1, 2, 3].map((i) => (
                <span
                    key={i}
                    className="flex shrink-0 items-center gap-3 px-8 text-sm font-semibold"
                >
                    <TriangleAlert className="size-4 shrink-0" aria-hidden />
                    {maintenance}
                </span>
            ))}
        </div>
    );

    return (
        <div
            role="alert"
            className="maintenance-banner sticky top-0 z-[60] w-full overflow-hidden bg-amber-400 py-1.5 text-amber-950"
        >
            <div className="maintenance-track">
                {group(false)}
                {group(true)}
            </div>
        </div>
    );
}
