<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/** One employee's saved line of a contract-labour client's Register of Wages for a month. */
class ContractWage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'employee_id', 'client_id', 'month', 'year', 'category', 'rate_per_day', 'paid_days', 'ot_hours',
        'figures', 'parameters', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_day' => 'decimal:2',
            'paid_days' => 'decimal:2',
            'ot_hours' => 'decimal:2',
            'figures' => 'array',
            'parameters' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
