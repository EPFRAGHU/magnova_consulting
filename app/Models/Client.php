<?php

namespace App\Models;

use App\Support\BonusRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property Carbon|null $contract_start
 * @property Carbon|null $contract_end
 * @property Carbon|null $buyer_order_date
 * @property string|null $bonus_value
 * @property string|null $bonus_ceiling
 * @property string|null $bonus_value_above
 * @property bool|null $bonus_in_esic
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
        'bonus_type', 'bonus_value', 'bonus_prorate', 'bonus_limit_mode', 'bonus_ceiling', 'bonus_value_above', 'bonus_billed', 'bonus_in_esic',
        'service_charge_on_basic', 'gst_exempt', 'esic_unit_mode', 'payroll_profile', 'principal_employer_name',
        'principal_employer_address', 'principal_employer_gstin', 'contract_start', 'contract_end', 'register_contractor_address', 'buyer_order_no', 'buyer_order_date',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'bonus_enabled' => 'boolean',
            'bonus_prorate' => 'boolean',
            'bonus_billed' => 'boolean',
            'bonus_in_esic' => 'boolean',
            'service_charge_on_basic' => 'boolean',
            'gst_exempt' => 'boolean',
            'contract_start' => 'date:Y-m-d',
            'contract_end' => 'date:Y-m-d',
            'buyer_order_date' => 'date:Y-m-d',
        ];
    }

    /** This client's bonus rule (type, value, wage limit, billing and ESIC treatment). */
    public function bonusRule(): BonusRule
    {
        return BonusRule::forClient($this);
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
