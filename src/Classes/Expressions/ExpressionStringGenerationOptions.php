<?php

namespace TsWink\Classes\Expressions;

class ExpressionStringGenerationOptions
{
    /** @var bool */
    public $indentUseSpaces = true;

    /** @var int */
    public $indentNumberOfSpaces = 4;

    public bool $useSingleQuotesForImports = false;

    public bool $useInterfaceInsteadOfClass = false;

    public bool $useSemicolon = true;

    public bool $forcePropertiesOptional = true;

    public bool $createSeparateClassForNewModels = false;
}
