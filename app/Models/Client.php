<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Client extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'unit_label', 'address', 'gstin', 'sac_code', 'gemc_no',
        'billing_name', 'billing_address',
        'contact_person', 'contact_phone', 'contact_email', 'active', 'bonus_enabled',
        'service_charge_on_basic', 'gst_exempt',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'bonus_enabled' => 'boolean',
            'service_charge_on_basic' => 'boolean',
            'gst_exempt' => 'boolean',
        ];
    }

    public function billingName(): string
    {
        return $this->billing_name ?: $this->name;
    }

    public function billingAddress(): ?string
    {
        return $this->billing_address ?: $this->address;
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
