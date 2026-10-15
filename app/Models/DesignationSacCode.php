<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class DesignationSacCode extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'designation', 'sac_code'];
}
