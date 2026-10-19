<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property Carbon|null $contract_start
 * @property Carbon|null $contract_end
 * @property Carbon|null $buyer_order_date
 */
class Client extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const ESIC_UNIT_MODES = ['statutory', 'ceiling', 'cutoff'];

    public const PAYROLL_PROFILES = ['standard', 'contract_labour'];

    protected $fillable = [
        'tenant_id', 'name', 'unit_label', 'address', 'gstin', 'sac_code', 'gemc_no',
        'billing_name', 'billing_address',
        'contact_person', 'contact_phone', 'contact_email', 'active', 'bonus_enabled',
        'service_charge_on_basic', 'gst_exempt', 'esic_unit_mode', 'payroll_profile', 'principal_employer_name',
        'principal_employer_address', 'principal_employer_gstin', 'contract_start', 'contract_end', 'register_contractor_address', 'buyer_order_no', 'buyer_order_date',
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
            'buyer_order_date' => 'date:Y-m-d',
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

    /** Contractor address printed on the contract-labour registers: the client's override, else Magnova's own address. */
    public function contractorAddress(): ?string
    {
        return $this->register_contractor_address ?: Tenant::find($this->tenant_id)?->address;
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
