<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Client extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const ESIC_UNIT_MODES = ['statutory', 'ceiling', 'cutoff'];

    public const PAYROLL_PROFILES = ['standard', 'contract_labour'];

    protected $fillable = [
        'tenant_id', 'name', 'unit_label', 'address', 'gstin', 'sac_code', 'gemc_no',
        'billing_name', 'billing_address',
        'contact_person', 'contact_phone', 'contact_email', 'active', 'bonus_enabled',
        'service_charge_on_basic', 'gst_exempt', 'esic_unit_mode', 'payroll_profile', 'principal_employer_name',
        'principal_employer_address', 'principal_employer_gstin', 'contract_start', 'contract_end', 'register_contractor_address',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'bonus_enabled' => 'boolean',
            'service_charge_on_basic' => 'boolean',
            'gst_exempt' => 'boolean',
            'contract_start' => 'date:Y-m-d',
            'contract_end' => 'date:Y-m-d',
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
