<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    protected $fillable = [
        'invoice_id', 'employee_id', 'working_days', 'rate_per_day', 'wage_amount',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'decimal:2',
            'rate_per_day' => 'decimal:2',
            'wage_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
