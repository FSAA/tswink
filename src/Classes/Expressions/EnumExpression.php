<?php

declare(strict_types=1);

namespace TsWink\Classes\Expressions;

use ReflectionEnum;
use UnitEnum;

class EnumExpression extends ClassExpression
{
    public static function tryParse(string $text, ?ClassExpression &$enum): bool
    {
        $namespace = null;
        $enumName = null;
        $matches = [];
        // Get the namespace form the file text
        if (preg_match('/namespace\\n* *(.+)\\n*;/', $text, $matches)) {
            $namespace = $matches[1];
        }
        if (preg_match('/^enum\s+([a-zA-Z_]+[a-zA-Z0-9_]*)\s*:/m', $text, $matches)) {
            $enumName = $matches[1];
        }
        if (!$enumName || !$namespace) {
            return false;
        }

        $enum = new EnumExpression();
        $enum->name = $enumName;
        $enum->namespace = $namespace;
        $enum->baseClassName = 'Enum';

        self::processClassMembers($enum);

        return true;
    }

    private static function processClassMembers(EnumExpression $enum): void
    {
        /** @var class-string<UnitEnum> */
        $className = $enum->fullyQualifiedClassName();
        $reflectionEnum = new ReflectionEnum($className);
        $parentConstants = [];
        $parentClass = $reflectionEnum->getParentClass();
        if ($parentClass && $parentClass instanceof ReflectionEnum) {
            $parentConstants = $parentClass->getCases();
        }
        foreach (array_diff($reflectionEnum->getCases(), $parentConstants) as $case) {
            $classMember = ClassMemberExpression::fromCase($case, $reflectionEnum->getBackingType());
            if ($classMember) {
                $enum->members[$classMember->name] = $classMember;
            }
        }
    }
}
