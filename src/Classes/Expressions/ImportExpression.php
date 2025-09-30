<?php

namespace TsWink\Classes\Expressions;

class ImportExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $target;

    public bool $isInternal = true;

    public bool $isPivot = false;

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        return "import " . ($options->useInterfaceInsteadOfClass ? 'type ' : '') . $this->name . " from "
            . $this->getTypeScriptQuote($options) . $this->target . $this->getTypeScriptQuote($options);
    }

    public function getTypeScriptQuote(ExpressionStringGenerationOptions $options): string
    {
        return $options->useSingleQuotesForImports ? '\'' : '"';
    }

    /**
     * Create a SetRequired import expression
     */
    public static function createSetRequiredImport(): ImportExpression
    {
        $import = new ImportExpression();
        $import->name = '{ SetRequired }';
        $import->target = '@universite-laval/script-components';
        $import->isInternal = false;
        return $import;
    }
}
