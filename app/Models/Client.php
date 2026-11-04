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
 * @property int|null $billing_from_day
 * @property int|null $billing_to_day
 */
class Client extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const ESIC_UNIT_MODES = ['statutory', 'ceiling', 'cutoff'];

    public const PAYROLL_PROFILES = ['standard', 'contract_labour'];

    /** Tax invoice designs for regular clients: 'aad_college' (AAD Gopalpur, rounded to the rupee) and 'indian_bank_zonal'. */
    public const INVOICE_LAYOUTS = ['standard', 'aad_college', 'indian_bank_zonal'];

    /** Designs whose GST is charged on the taxable value that includes the service charge. */
    public const GST_ON_SERVICE_CHARGE_LAYOUTS = ['aad_college', 'indian_bank_zonal'];

    protected $fillable = [
        'tenant_id', 'name', 'unit_label', 'address', 'gstin', 'sac_code', 'gemc_no',
        'billing_name', 'billing_address',
        'contact_person', 'contact_phone', 'contact_email', 'active', 'bonus_enabled',
        'bonus_type', 'bonus_value', 'bonus_prorate', 'bonus_limit_mode', 'bonus_ceiling', 'bonus_value_above', 'bonus_billed', 'bonus_in_esic',
        'service_charge_on_basic', 'gst_exempt', 'esic_unit_mode', 'payroll_profile', 'principal_employer_name',
        'principal_employer_address', 'principal_employer_gstin', 'contract_start', 'contract_end', 'register_contractor_address', 'buyer_order_no', 'buyer_order_date',
        'billing_from_day', 'billing_from_month', 'billing_to_day', 'billing_to_month', 'invoice_layout',
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

    /**
     * The billing period this client's invoices print for a wage month, or null for the whole calendar month.
     * "From" sits in the invoice month or the one before it, "to" in the invoice month or the one after; a day past
     * the end of a short month (31 in June) becomes that month's last day.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function billingPeriodFor(int $month, int $year): ?array
    {
        if (! $this->billing_from_day || ! $this->billing_to_day) {
            return null;
        }

        $anchor = Carbon::create($year, $month, 1);
        $from = $this->billing_from_month === 'previous' ? $anchor->copy()->subMonthNoOverflow() : $anchor->copy();
        $to = $this->billing_to_month === 'next' ? $anchor->copy()->addMonthNoOverflow() : $anchor->copy();

        $start = $from->day(min($this->billing_from_day, $from->daysInMonth));
        $end = $to->day(min($this->billing_to_day, $to->daysInMonth));

        return $end->lt($start) ? null : [$start, $end];
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
