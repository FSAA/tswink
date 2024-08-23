<?php

namespace TsWink\Classes\Expressions;

class TypeExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var bool */
    public $isCollection;

    public function isPrimitive(): bool
    {
        return in_array($this->name, ["string", "number", "boolean", "any"]);
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($this->isCollection) {
            return $this->name . "[]";
        }
        return $this->name;
    }
}
