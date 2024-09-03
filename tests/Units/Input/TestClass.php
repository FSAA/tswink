<?php

namespace TsWinkTests\Units\Input;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

class TestClass extends Model
{
    const TEST_CONST = 45.6;

    /**
     * @return BelongsTo<User,self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<User,self>
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getTestAccessorAttribute(): string
    {
        return "";
    }

    public function getTesAnyAccessorAttribute(): DateTime
    {
        return new DateTime();
    }
}
