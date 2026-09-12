<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasInternalKeys;
use Stancl\Tenancy\Database\Concerns\TenantRun;

class Tenant extends Model implements TenantContract
{
    use HasInternalKeys, TenantRun;

    protected $fillable = [
        'name', 'slug', 'gstin', 'pan', 'contact_email', 'contact_phone',
        'address', 'bank_name', 'bank_account_no', 'bank_ifsc', 'bank_branch', 'subscription_status',
        'terms_and_conditions',
    ];

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getAttribute('id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
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
