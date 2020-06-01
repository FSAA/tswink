<?php

namespace TsWink\Classes\Expressions;

class ImportExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $target;

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        return "import " . $this->name . " from \"" . $this->target . "\"";
    }
}
