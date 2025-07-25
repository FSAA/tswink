<?php

namespace TsWinkTests\Units\Input;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

/**
 * @property bool $tswinkOverride
 * @tswink-property string $tswinkOverride
 * @property array $anyArray
 * @property-write string[] $stringArray
 * @property-write array<array<string>> $deepStringArray
 * @property-read array{stringProperty:string,numberProperty:int,complexProperty:array{key:string},subArray:array<string,string>} $associativeArray
 * @property-read array<int,array{foo:bool}> $complexArray
 * @property-read int $student_count
 * @property-read int|null $nullable_student_count
 * @property-read object|null $test_nullable_any_count
 * @phpstan-ignore missingType.iterableValue
 */
class TestClass extends Model
{
    const TEST_CONST = 45.6;
    const TEST_CONST_STRING = "test";
    const TEST_CONST_ARRAY = ["test", 123, true];

    /**
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<User,$this>
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

    public function getStringOrIntAccessorAttribute(): string | int
    {
        return "";
    }

    public function getTswinkOverrideAttribute(): int
    {
        return 0;
    }
}
