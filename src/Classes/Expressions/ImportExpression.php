<?php

namespace TsWink\Classes\Expressions;

class ImportExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $target;

    public bool $internal = true;

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        return "import " . ($options->useInterfaceInsteadOfClass ? 'type ' : '') . $this->name . " from "
            . $this->getTypeScriptQuote($options) . $this->target . $this->getTypeScriptQuote($options);
    }

    public function getTypeScriptQuote(ExpressionStringGenerationOptions $options): string
    {
        return $options->useSingleQuotesForImports ? '\'' : '"';
    }
}
