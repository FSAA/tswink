<?php

namespace TsWink\Classes\Expressions;

use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use TsWink\Classes\Utils\StringUtils;

abstract class Expression
{
    public function getIndentExpression(ExpressionStringGenerationOptions $options): string
    {
        if ($options->indent_use_spaces) {
            return str_repeat(" ", $options->indent_number_of_spaces);
        } else {
            return "\t";
        }
    }

    public function indent(string $text, int $indentLevel, ExpressionStringGenerationOptions $options)
    {
        return StringUtils::indent($text, $indentLevel, $this->getIndentExpression($options));
    }

    abstract public function toTypeScript(ExpressionStringGenerationOptions $options);
}
