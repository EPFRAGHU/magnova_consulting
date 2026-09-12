<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Employee extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'client_id', 'employee_code', 'name', 'uan', 'epf_member_id',
        'esic_no', 'skill_category', 'designation', 'rate_per_day', 'date_of_joining',
        'status', 'bank_name', 'bank_account_no', 'bank_ifsc',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_day' => 'decimal:2',
            'date_of_joining' => 'date:Y-m-d',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }
}
