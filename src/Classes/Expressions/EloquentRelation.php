<?php

namespace TsWink\Classes\Expressions;

class EloquentRelation
{
    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var string */
    public $target_class_name;

    public static function parse($relation): EloquentRelation
    {
        $eloquentRelation = new EloquentRelation();
        $eloquentRelation->name = $relation['relationName'];
        $eloquentRelation->target_class_name = substr($relation['targetClass'], strrpos($relation['targetClass'], '\\') + 1);
        $eloquentRelation->type = $relation['relationType'];
        return $eloquentRelation;
    }
}
