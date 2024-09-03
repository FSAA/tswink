<?php

namespace TsWink\Classes\Expressions;

use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

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

    public static function fromReflectionMethod(\ReflectionMethod $method): TypeExpression
    {
        $type = new TypeExpression();
        $type->name = self::convertPhpToTypescriptType(self::getReturnTypeName($method->getReturnType()));
        $type->isCollection = false;
        return $type;
    }

    public static function fromReflectionNamedType(ReflectionNamedType $reflectionNamedType): TypeExpression
    {
        $type = new TypeExpression();
        $type->name = self::convertPhpToTypescriptType(self::getReturnTypeName($reflectionNamedType));
        $type->isCollection = false;
        return $type;
    }

    private static function getReturnTypeName(?ReflectionType $returnType): string
    {
        if (!$returnType instanceof \ReflectionNamedType) {
            return '';
        }
        return $returnType->getName();
    }

    public static function fromReflectionProperty(ReflectionProperty $property): TypeExpression
    {
        $type = new TypeExpression();
        $type->name = self::convertPhpToTypescriptType(self::getReturnTypeName($property->getType()));
        $type->isCollection = false;
        return $type;
    }

    public static function fromConstant(mixed $value): TypeExpression
    {
        $type = new TypeExpression();
        $type->name = self::convertPhpToTypescriptType(gettype($value));
        $type->isCollection = false;
        return $type;
    }

    private static function convertPhpToTypescriptType(string $phpType): string
    {
        return match ($phpType) {
            'int', 'float', 'double', 'integer' => 'number',
            'bool', 'boolean' => 'boolean',
            'string' => 'string',
            default => 'any',
        };
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($this->isCollection) {
            return $this->name . "[]";
        }
        return $this->name;
    }
}
