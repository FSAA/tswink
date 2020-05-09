<?php

namespace TsWink\Classes\Expressions;

class TypeExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var bool */
    public $is_collection;

    public function isPrimitive()
    {
        return in_array($this->name, ["string", "number", "boolean", "any"]);
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($this->is_collection) {
            return $this->name . "[]";
        } else {
            return $this->name;
        }
    }
}
