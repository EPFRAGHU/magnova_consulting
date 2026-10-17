<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Wage extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'employee_id', 'month', 'year', 'working_days', 'rate_per_day',
        'gross_wages', 'bonus', 'epf_employee', 'esic_employee', 'esic_snapshot', 'net_salary',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'decimal:2',
            'rate_per_day' => 'decimal:2',
            'gross_wages' => 'decimal:2',
            'bonus' => 'decimal:2',
            'epf_employee' => 'decimal:2',
            'esic_employee' => 'decimal:2',
            'esic_snapshot' => 'array',
            'net_salary' => 'decimal:2',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
