<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
