<?php

namespace TsWink\Classes\Expressions;

use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use TsWink\Classes\Utils\StringUtils;

abstract class Expression
{
    public function getIndentExpression(ExpressionStringGenerationOptions $options): string
    {
        if ($options->indentUseSpaces) {
            return str_repeat(" ", $options->indentNumberOfSpaces);
        }
        return "\t";
    }

    public function indent(string $text, int $indentLevel, ExpressionStringGenerationOptions $options): string
    {
        return StringUtils::indent($text, $indentLevel, $this->getIndentExpression($options));
    }

    abstract public function toTypeScript(ExpressionStringGenerationOptions $options): string;
}
