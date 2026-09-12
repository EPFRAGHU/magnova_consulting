import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

// Muted, palette-consistent status colors (see theme spec: success/warning/neutral + a
// quiet danger tone for the same rose family, no saturated colors).
const SUCCESS =
    'bg-[#E4EFE7] text-[#356B4B] border-[#CFE2D5] dark:bg-[#1B2A20] dark:text-[#8FBF9E] dark:border-[#2A3F30]';
const WARNING =
    'bg-[#F5E7D6] text-[#96631A] border-[#EAD6B4] dark:bg-[#2E2412] dark:text-[#D6A85C] dark:border-[#43371C]';
const NEUTRAL =
    'bg-[#F0E9E7] text-[#948581] border-[#E3D6D3] dark:bg-[#241C1A] dark:text-[#B0A29D] dark:border-[#3A2E2A]';
const DANGER =
    'bg-[#F5E1DF] text-[#A13A32] border-[#E9C7C3] dark:bg-[#2E1815] dark:text-[#D9756C] dark:border-[#432420]';

const COLORS: Record<string, string> = {
    draft: NEUTRAL,
    sent: WARNING,
    paid: SUCCESS,
    pending: NEUTRAL,
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
