<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $expires_at
 * @property Carbon|null $last_sent_at
 * @property Carbon|null $consumed_at
 */
class LoginOtp extends Model
{
    protected $fillable = [
        'user_id',
        'mobile_number',
        'purpose',
        'code_hash',
        'attempts',
        'max_attempts',
        'expires_at',
        'last_sent_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
