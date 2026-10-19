<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property Carbon|null $date_of_joining
 * @property Carbon|null $esic_covered_from
 */
class Employee extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'client_id', 'employee_code', 'name', 'gender', 'uan', 'epf_member_id',
        'esic_no', 'esic_applicable', 'esic_covered_from', 'skill_category', 'designation', 'rate_per_day', 'date_of_joining',
        'status', 'bank_name', 'bank_account_no', 'bank_ifsc',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_day' => 'decimal:2',
            'date_of_joining' => 'date:Y-m-d',
            'esic_applicable' => 'boolean',
            'esic_covered_from' => 'date:Y-m-d',
        ];
    }

    public const SKILL_ORDER = ['high_skilled', 'skilled', 'semi_skilled', 'unskilled'];

    public function skillLabel(): string
    {
        return ucwords(str_replace('_', '-', (string) $this->skill_category), '-');
    }

    public function skillSortKey(): string
    {
        $rank = array_search($this->skill_category, self::SKILL_ORDER, true);

        return sprintf('%d|%s', $rank === false ? count(self::SKILL_ORDER) : $rank, mb_strtolower((string) $this->name));
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeOrderBySkill(Builder $query): Builder
    {
        // One WHEN per entry of SKILL_ORDER (in that order); anything else sorts last.
        return $query
            ->orderByRaw(
                'CASE employees.skill_category WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END',
                self::SKILL_ORDER,
            )
            ->orderBy('employees.name');
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<Wage, $this> */
    public function wages(): HasMany
    {
        return $this->hasMany(Wage::class);
    }
}
