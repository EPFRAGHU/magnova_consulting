<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/** One dated set of ESIC rules for an establishment. Rows are added, never edited. */
class EsicSetting extends Model
{
    use BelongsToTenant;

    public const MODES = ['statutory', 'capped', 'cutoff'];

    public const ROUNDING = ['nearest', 'up', 'down'];

    protected $fillable = [
        'tenant_id', 'effective_from', 'establishment_covered', 'calculation_mode', 'coverage_ceiling',
        'employee_rate', 'employer_rate', 'daily_wage_exemption', 'include_bonus', 'rounding', 'note',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date:Y-m-d',
            'establishment_covered' => 'boolean',
            'coverage_ceiling' => 'float',
            'employee_rate' => 'float',
            'employer_rate' => 'float',
            'daily_wage_exemption' => 'float',
            'include_bonus' => 'boolean',
        ];
    }

    /** Values used when an establishment has saved nothing of its own (from config/magnova.php). */
    public static function defaults(): array
    {
        $config = config('magnova.esic');

        return [
            'establishment_covered' => (bool) $config['establishment_covered'],
            'calculation_mode' => $config['calculation_mode'],
            'coverage_ceiling' => (float) $config['coverage_ceiling'],
            'employee_rate' => (float) $config['employee_rate'],
            'employer_rate' => (float) $config['employer_rate'],
            'daily_wage_exemption' => $config['daily_wage_exemption'] === null ? null : (float) $config['daily_wage_exemption'],
            'include_bonus' => (bool) $config['include_bonus'],
            'rounding' => $config['rounding'],
        ];
    }

    public static function modeLabel(string $mode): string
    {
        return match ($mode) {
            'cutoff' => 'No ESIC above the ceiling (NOT statutory: nil whenever wages are above the ceiling, even for an employee covered earlier in the period)',
            'capped' => 'Capped at the ceiling (NOT statutory: contribution wages are limited to the ceiling and nobody leaves coverage)',
            default => 'Statutory (ESI Act: the ceiling decides who becomes covered, coverage continues to the end of the contribution period, contributions are on actual wages)',
        };
    }
}
