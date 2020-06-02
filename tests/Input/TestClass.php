<?php

namespace TsWinkTests\Input;

use Illuminate\Database\Eloquent\Model;

class TestClass extends Model
{
    protected $table = 'events';

    const TEST_CONST = 45.6;

    /** @var array */
    protected $modelRelations = [
        [
            'relationName' => 'eventType',
            'relationType' => 'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'targetClass' => 'App\Models\EventType',
            'foreignKey' => 'event_type_id',
            'ownerKey' => 'id'
        ],
        [
            'relationName' => 'introductions',
            'relationType' => 'Illuminate\Database\Eloquent\Relations\HasMany',
            'targetClass' => 'App\Models\Introduction',
            'foreignKey' => 'event_id',
            'localKey' => 'id'
        ]
    ];

    public function getModelRelations(): array
    {
        return $this->modelRelations;
    }

    public function getTestAccessorAttribute()
    {
        return "";
    }
}