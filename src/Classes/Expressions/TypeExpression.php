<?php

namespace TsWink\Classes\Expressions;

use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;

class TypeExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var bool */
    public $isCollection;

    private bool $forceIsPrimitive = false;

    public function isPrimitive(): bool
    {
        return $this->forceIsPrimitive || in_array($this->name, ["string", "number", "boolean", "any"]);
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

    public static function fromPropertyDecorator(Property|PropertyRead|PropertyWrite $propertyTag): ?TypeExpression
    {
        $type = new TypeExpression();
        $reflectionType = $propertyTag->getType();
        if (!$reflectionType) {
            return null;
        }
        $propertyType = (string) $reflectionType;
        $type->name = self::decoratorTypeToString($reflectionType);
        $type->forceIsPrimitive = true;
        return $type;
    }

    private static function decoratorTypeToString(Type $reflectionType): string
    {
        switch (get_class($reflectionType)) {
            case Array_::class:
                return self::convertDocumentorArrayToTypescriptType($reflectionType);
            case ArrayShape::class:
                return self::convertDocumentorArrayShapeToTypescriptType($reflectionType);
            default:
                return self::convertPhpToTypescriptType((string) $reflectionType);
        }
    }

    private static function convertDocumentorArrayToTypescriptType(Array_ $reflectionType): string
    {
        // We use reflection to get the keyType because the property is protected and calling getKeyType() will return their default value instead of null
        $realKeyType = (new ReflectionClass(Array_::class))->getProperty('keyType')->getValue($reflectionType);
        if ($realKeyType === null) {
            return 'Array<' . self::decoratorTypeToString($reflectionType->getValueType()) . '>';
        }
        return '{ '
            . self::decoratorTypeToString($reflectionType->getKeyType())
            . ': '
            . self::decoratorTypeToString($reflectionType->getValueType())
            . ' }';
    }

    private static function convertDocumentorArrayShapeToTypescriptType(ArrayShape $arrayShape): string
    {
        $shape = '{ ';
        foreach ($arrayShape->getItems() as $item) {
            $key = $item->getKey();
            $value = self::decoratorTypeToString($item->getValue());
            $shape .= $key . ': ' . $value . ', ';
        }
        $shape = rtrim($shape, ', ') . ' }';
        return $shape;
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
