<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property Carbon $date
 */
class Attendance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'employee_id', 'date', 'status', 'ot_hours',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'ot_hours' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
