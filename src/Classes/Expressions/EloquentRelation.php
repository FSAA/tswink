<?php

namespace TsWink\Classes\Expressions;

class EloquentRelation
{
    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var string */
    public $targetClassName;

    /**
     * @param array{relationName: string, relationType: string, targetClass: string} $relation
     */
    public static function parse(array $relation): EloquentRelation
    {
        $eloquentRelation = new EloquentRelation();
        $eloquentRelation->name = $relation['relationName'];
        $eloquentRelation->targetClassName = substr($relation['targetClass'], strrpos($relation['targetClass'], '\\') + 1);
        $eloquentRelation->type = $relation['relationType'];
        return $eloquentRelation;
    }
}
