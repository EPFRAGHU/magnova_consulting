<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'client_id', 'invoice_no', 'ref_no', 'invoice_date', 'due_date',
        'place_of_supply', 'reverse_charge', 'month', 'year',
        'rate_per_day', 'no_of_persons', 'no_of_duties', 'base_amount',
        'epf_employer', 'epf_admin', 'edli_cont', 'esi_employer', 'taxable_value', 'service_charge',
        'cgst', 'sgst', 'grand_total', 'status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'reverse_charge' => 'boolean',
            'rate_per_day' => 'decimal:2',
            'no_of_duties' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'epf_employer' => 'decimal:2',
            'epf_admin' => 'decimal:2',
            'edli_cont' => 'decimal:2',
            'esi_employer' => 'decimal:2',
            'taxable_value' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }
}
