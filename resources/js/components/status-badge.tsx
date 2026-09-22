import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const SUCCESS =
    'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300';
const WARNING =
    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
const NEUTRAL =
    'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300';
const DANGER =
    'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300';

const COLORS: Record<string, string> = {
    draft: NEUTRAL,
    sent: WARNING,
    paid: SUCCESS,
    pending: NEUTRAL,
    not_started: NEUTRAL,
    in_progress: WARNING,
    processed: SUCCESS,
    blocked: DANGER,
    present: SUCCESS,
    absent: DANGER,
    holiday: NEUTRAL,
    leave: WARNING,
    sunday: SUCCESS,
    active: SUCCESS,
    inactive: NEUTRAL,
    left: DANGER,
};

const LABELS: Record<string, string> = {
    holiday: 'Holiday',
};

export function StatusBadge({ status }: { status: string }) {
    return (
        <Badge
            variant="outline"
            className={cn('capitalize', COLORS[status] ?? '')}
        >
            {LABELS[status] ?? status.replace('_', ' ')}
        </Badge>
    );
}
