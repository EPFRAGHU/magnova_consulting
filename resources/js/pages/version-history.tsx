import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { dashboard, versionHistory } from '@/routes';

type Change = { type: 'new' | 'improved' | 'fixed'; text: string };

type Release = {
    version: string;
    released_at: string;
    title: string;
    changes: Change[];
};

const TYPE_STYLE: Record<Change['type'], { label: string; className: string }> =
    {
        new: {
            label: 'New',
            className:
                'border-transparent bg-emerald-600 text-white dark:bg-emerald-500',
        },
        improved: {
            label: 'Improved',
            className:
                'border-transparent bg-blue-600 text-white dark:bg-blue-500',
        },
        fixed: {
            label: 'Fixed',
            className:
                'border-transparent bg-amber-500 text-white dark:bg-amber-400 dark:text-amber-950',
        },
    };

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

// 2026-11-04 07:30 -> 04 Nov 2026, 07:30 AM (read from the text, so the time zone cannot move it; India time).
function formatReleased(value: string): string {
    const [date, time = '00:00'] = value.split(' ');
    const [year, month, day] = date.split('-').map(Number);
    const [hours, minutes] = time.split(':').map(Number);
    const hour12 = hours % 12 === 0 ? 12 : hours % 12;

    return `${String(day).padStart(2, '0')} ${MONTHS[month - 1]} ${year}, ${String(hour12).padStart(2, '0')}:${String(minutes).padStart(2, '0')} ${hours < 12 ? 'AM' : 'PM'}`;
}

export default function VersionHistory({ releases }: { releases: Release[] }) {
    const { appInfo } = usePage().props;

    return (
        <>
            <Head title="Version history" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Version history"
                    description={`Current version ${appInfo.version}. Times are India time. The project started ${formatReleased(appInfo.startedAt)}.`}
                />

                <ol className="max-w-3xl">
                    {releases.map((release, index) => (
                        <li
                            key={release.version}
                            className="relative border-l-2 border-slate-200 pb-8 pl-6 last:border-transparent last:pb-0 dark:border-slate-700"
                        >
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'absolute top-1.5 -left-[9px] size-4 rounded-full border-2 border-white dark:border-slate-950',
                                    index === 0
                                        ? 'bg-[#1764ed]'
                                        : 'bg-slate-400 dark:bg-slate-500',
                                )}
                            />
                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span
                                    className={cn(
                                        'rounded-md px-2.5 py-0.5 text-sm font-bold tabular-nums',
                                        index === 0
                                            ? 'bg-[#1764ed] text-white'
                                            : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
                                    )}
                                >
                                    v{release.version}
                                </span>
                                {index === 0 && (
                                    <Badge className="border-transparent bg-emerald-600 text-white dark:bg-emerald-500">
                                        Latest
                                    </Badge>
                                )}
                                <time className="text-muted-foreground text-xs">
                                    {formatReleased(release.released_at)}
                                </time>
                            </div>
                            <h2 className="mt-2 text-base font-semibold">
                                {release.title}
                            </h2>
                            <ul className="mt-2 grid gap-1.5">
                                {release.changes.map((change, i) => (
                                    <li
                                        key={i}
                                        className="flex items-start gap-2 text-sm"
                                    >
                                        <Badge
                                            className={cn(
                                                'mt-0.5 w-[4.5rem] shrink-0 justify-center text-[10px]',
                                                TYPE_STYLE[change.type]
                                                    .className,
                                            )}
                                        >
                                            {TYPE_STYLE[change.type].label}
                                        </Badge>
                                        <span>{change.text}</span>
                                    </li>
                                ))}
                            </ul>
                        </li>
                    ))}
                </ol>
            </div>
        </>
    );
}

VersionHistory.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Version history', href: versionHistory() },
    ],
};
