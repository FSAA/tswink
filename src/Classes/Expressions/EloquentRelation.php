<?php

namespace TsWink\Classes\Expressions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TRelatedModel of Model
 */
class EloquentRelation
{
    /** @var string */
    public $name;

    /** @var class-string<Relation<TRelatedModel,Model,Collection<int,TRelatedModel>|TRelatedModel>> */
    public $type;

    /** @var class-string<TRelatedModel> */
    public $targetClassName;

    public ?string $pivotAccessor = null;

    public ?string $pivotTable = null;

    /** @var array<string> */
    public array $pivotColumns = [];

    /**
     * @param array{relationName:string,relationType:class-string<Relation<TRelatedModel,Model,Collection<int,TRelatedModel>|TRelatedModel>>,targetClass:class-string<TRelatedModel>,pivotAccessor?:string|null,pivotTable?:string|null,pivotColumns?:array<string>} $relation
     * @return EloquentRelation<TRelatedModel>
     */
    public static function parse(array $relation): EloquentRelation
    {
        /** @var EloquentRelation<TRelatedModel> $eloquentRelation */
        $eloquentRelation = new EloquentRelation();
        $eloquentRelation->name = $relation['relationName'];
        $eloquentRelation->targetClassName = $relation['targetClass'];
        $eloquentRelation->type = $relation['relationType'];
        $eloquentRelation->pivotAccessor = $relation['pivotAccessor'] ?? null;
        $eloquentRelation->pivotTable = $relation['pivotTable'] ?? null;
        $eloquentRelation->pivotColumns = $relation['pivotColumns'] ?? [];
        return $eloquentRelation;
    }

    public function classNameToTypeScriptType(): string
    {
        return substr($this->targetClassName, strrpos($this->targetClassName, '\\') + 1);
    }
}
