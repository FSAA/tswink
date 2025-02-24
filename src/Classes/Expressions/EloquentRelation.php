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

    /**
     * @param array{relationName:string,relationType:class-string<Relation<TRelatedModel,Model,Collection<int,TRelatedModel>|TRelatedModel>>,targetClass:class-string<TRelatedModel>} $relation
     * @return EloquentRelation<TRelatedModel>
     */
    public static function parse(array $relation): EloquentRelation
    {
        /** @var EloquentRelation<TRelatedModel> $eloquentRelation */
        $eloquentRelation = new EloquentRelation();
        $eloquentRelation->name = $relation['relationName'];
        $eloquentRelation->targetClassName = $relation['targetClass'];
        $eloquentRelation->type = $relation['relationType'];
        return $eloquentRelation;
    }

    public function classNameToTypeScriptType(): string
    {
        return substr($this->targetClassName, strrpos($this->targetClassName, '\\') + 1);
    }
}
