<?php

namespace TsWink\Classes\Expressions\Contracts;

use TsWink\Classes\Expressions\GenerationContext;
use TsWink\Classes\Expressions\ImportExpression;

interface RequiresImports
{
    /**
     * @return ImportExpression[]
     */
    public function getRequiredImports(GenerationContext $context): array;
}
