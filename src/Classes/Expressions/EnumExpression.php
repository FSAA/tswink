<?php

declare(strict_types=1);

namespace TsWink\Classes\Expressions;

use ReflectionEnum;
use ReflectionMethod;
use UnitEnum;

class EnumExpression extends ClassExpression
{
    /** @var ServiceMethodExpression[] */
    public $serviceMethods = [];
    public static function tryParse(string $text, ?ClassExpression &$enum, ExpressionStringGenerationOptions $codeGenerationOptions): bool
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
        self::processServiceMethods($enum);

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

    private static function processServiceMethods(EnumExpression $enum): void
    {
        /** @var class-string<UnitEnum> */
        $className = $enum->fullyQualifiedClassName();
        $reflectionEnum = new ReflectionEnum($className);

        foreach ($reflectionEnum->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            $serviceMethod = ServiceMethodExpression::fromReflectionMethod($method, $enum->name);
            if ($serviceMethod) {
                $enum->serviceMethods[] = $serviceMethod;
            }
        }
    }

    public function generateServiceObject(ExpressionStringGenerationOptions $options): string
    {
        if (empty($this->serviceMethods)) {
            return '';
        }

        $serviceContent = "export const {$this->name}Service = {\n";
        $methodsContent = '';

        foreach ($this->serviceMethods as $serviceMethod) {
            $methodsContent .= $serviceMethod->toTypeScript($options, new GenerationContext()) . ",\n";
        }

        $serviceContent .= $this->indent(trim($methodsContent, "\n"), 1, $options) . "\n";
        $serviceContent .= "}\n";

        return $serviceContent;
    }
}
