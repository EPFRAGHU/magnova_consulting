<?php

namespace App\Support;

use App\Models\Client;

/**
 * How one client's bonus is worked out. The defaults are the old single rule: 8.33% of the monthly wage, nothing when the
 * wage is above Rs 21,000, shown on the invoice, and ESIC treated as the ESIC settings say.
 *
 * Types: 'percent' (of the monthly wage), 'per_day' (a rupee amount for each paid day), 'per_month' (a fixed amount,
 * optionally reduced for fewer paid days). Limits: 'up_to' (no bonus above the ceiling), 'none' (bonus on every wage) and
 * 'different_above' (another percentage or amount above the ceiling).
 */
final class BonusRule
{
    public const TYPES = ['percent', 'per_day', 'per_month'];

    public const LIMITS = ['up_to', 'none', 'different_above'];

    public function __construct(
        public readonly bool $enabled = false,
        public readonly string $type = 'percent',
        public readonly float $value = 8.33,
        public readonly bool $prorate = false,
        public readonly string $limit = 'up_to',
        public readonly float $ceiling = 21000.0,
        public readonly float $valueAbove = 0.0,
        public readonly bool $billed = true,
        public readonly ?bool $inEsic = null,
    ) {}

    public static function forClient(?Client $client, ?bool $enabled = null): self
    {
        $type = in_array($client?->bonus_type, self::TYPES, true) ? $client->bonus_type : 'percent';
        $defaultValue = $type === 'percent' ? (float) config('magnova.bonus_pct') : 0.0;

        return new self(
            enabled: $enabled ?? (bool) $client?->bonus_enabled,
            type: $type,
            value: $client?->bonus_value !== null ? (float) $client->bonus_value : $defaultValue,
            prorate: (bool) $client?->bonus_prorate,
            limit: in_array($client?->bonus_limit_mode, self::LIMITS, true) ? $client->bonus_limit_mode : 'up_to',
            ceiling: $client?->bonus_ceiling !== null ? (float) $client->bonus_ceiling : (float) config('magnova.bonus_eligibility_ceiling'),
            valueAbove: (float) ($client->bonus_value_above ?? 0),
            billed: $client?->bonus_billed !== false,
            inEsic: $client?->bonus_in_esic === null ? null : (bool) $client->bonus_in_esic,
        );
    }

    /** True for the original rule, which months before the current register rules still calculate their own way. */
    public function isOriginal(): bool
    {
        return $this->type === 'percent'
            && $this->limit === 'up_to'
            && abs($this->value - (float) config('magnova.bonus_pct')) < 0.0001
            && abs($this->ceiling - (float) config('magnova.bonus_eligibility_ceiling')) < 0.0001;
    }

    /** The bonus for one employee and month, to the paisa. $paidDays may be null for percentage rules. */
    public function amount(float $monthlyWage, ?float $paidDays, int $daysInMonth): float
    {
        if (! $this->enabled || ($monthlyWage <= 0 && ($paidDays ?? 0) <= 0)) {
            return 0.0;
        }

        $above = $this->limit !== 'none' && $monthlyWage > $this->ceiling;
        if ($above && $this->limit === 'up_to') {
            return 0.0;
        }

        $value = $above ? $this->valueAbove : $this->value;
        $days = max(0.0, $paidDays ?? 0.0);

        return match ($this->type) {
            'per_day' => round($days * $value, 2),
            'per_month' => round($this->prorate ? $value * min($days / max(1, $daysInMonth), 1.0) : $value, 2),
            default => round($monthlyWage * $value / 100, 2),
        };
    }

    /** The heading of the Bonus column of the Register of Wages. */
    public function heading(): string
    {
        $part = fn (float $value) => match ($this->type) {
            'per_day' => 'Rs.'.$this->plain($value).' per day worked',
            'per_month' => 'Rs.'.$this->plain($value).' per month'.($this->prorate ? ' (reduced for fewer days)' : ''),
            default => $this->plain($value).'% of Monthly Wage',
        };

        $limit = match ($this->limit) {
            'none' => 'no wage limit',
            'different_above' => $part($this->valueAbove).' if Monthly Wage above Rs.'.number_format($this->ceiling).'/-',
            default => 'nil if Monthly Wage above Rs.'.number_format($this->ceiling).'/-',
        };

        return 'Bonus @'.$part($this->value)." ({$limit})";
    }

    /** The label of the bonus line on the tax invoice. */
    public function invoiceLabel(): string
    {
        if ($this->isOriginal()) {
            return 'Bonus @ '.number_format((float) config('magnova.bonus_pct'), 2).'% on capped monthly wages';
        }

        $what = match ($this->type) {
            'per_day' => 'Rs.'.$this->plain($this->value).' per day worked',
            'per_month' => 'Rs.'.$this->plain($this->value).' per month'.($this->prorate ? ' (reduced for fewer days)' : ''),
            default => $this->plain($this->value).'% on monthly wages',
        };

        return 'Bonus @ '.$what;
    }

    private function plain(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
