<?php

namespace TsWinkTests\Units\Input;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Tag extends Model
{
    protected $fillable = ['name'];

    /**
     * @return BelongsToMany<TestClass,$this,Pivot,'assignment'>
     */
    public function testClasses(): BelongsToMany
    {
        return $this->belongsToMany(TestClass::class, 'test_class_tag')
            ->withPivot(['priority', 'assigned_at'])
            ->as('assignment');
    }
}
