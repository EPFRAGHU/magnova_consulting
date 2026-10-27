<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasInternalKeys;
use Stancl\Tenancy\Database\Concerns\TenantRun;

class Tenant extends Model implements TenantContract
{
    use HasInternalKeys, TenantRun;

    protected $fillable = [
        'name', 'slug', 'gstin', 'pan', 'pf_establishment_code', 'contact_email', 'contact_phone',
        'address', 'establishment_address1', 'establishment_address2', 'establishment_city',
        'establishment_district', 'establishment_pin', 'establishment_mobile',
        'establishment_email', 'establishment_website', 'bank_name', 'bank_account_no',
        'bank_ifsc', 'bank_branch', 'subscription_status',
        'terms_and_conditions',
    ];

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): mixed
    {
        return $this->getAttribute('id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Client, $this> */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
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
