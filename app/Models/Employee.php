<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Employee extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

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

    public function scopeOrderBySkill($query)
    {
        $cases = collect(self::SKILL_ORDER)
            ->map(fn (string $skill, int $rank) => "WHEN '{$skill}' THEN {$rank}")
            ->implode(' ');

        return $query
            ->orderByRaw("CASE employees.skill_category {$cases} ELSE ".count(self::SKILL_ORDER).' END')
            ->orderBy('employees.name');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }
}
