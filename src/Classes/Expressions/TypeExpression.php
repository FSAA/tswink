<?php

namespace TsWink\Classes\Expressions;

use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

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

    /**
     * @return array<TypeExpression>
     */
    public static function fromReflectionMethod(ReflectionMethod $method): array
    {
        $types = [];
        foreach (self::convertPhpToTypescriptType(self::getReturnTypeName($method->getReturnType())) as $typeName) {
            $type = new TypeExpression();
            $type->name = $typeName;
            $type->isCollection = false;
            $types[] = $type;
        }
        return $types;
    }

    /**
     * @return array<TypeExpression>
     */
    public static function fromReflectionNamedType(ReflectionNamedType $reflectionNamedType): array
    {
        $types = [];
        foreach (self::convertPhpToTypescriptType(self::getReturnTypeName($reflectionNamedType)) as $typeName) {
            $type = new TypeExpression();
            $type->name = $typeName;
            $type->isCollection = false;
            $types[] = $type;
        }
        return $types;
    }

    private static function getReturnTypeName(?ReflectionType $returnType): string
    {
        if ($returnType instanceof ReflectionUnionType) {
            $types = [];
            foreach ($returnType->getTypes() as $type) {
                $typeName = self::getReturnTypeName($type);
                if ($typeName === '') {
                    continue;
                }
                $types[] = $typeName;
            }
            return implode(' | ', $types);
        }
        if (!$returnType instanceof \ReflectionNamedType) {
            return '';
        }
        return $returnType->getName();
    }

    /**
     * @return null|array<TypeExpression>
     */
    public static function fromPropertyDecorator(Property|PropertyRead|PropertyWrite $propertyTag): ?array
    {
        $reflectionType = $propertyTag->getType();
        if (!$reflectionType) {
            return null;
        }

        $types = [];
        foreach (self::parseDecoratorType($reflectionType) as $typeName) {
            $type = new TypeExpression();
            $type->name = $typeName;
            $type->forceIsPrimitive = true;
            $types[] = $type;
        }
        return $types;
    }

    /**
     * @return array<string>
     */
    private static function parseDecoratorType(Type $reflectionType): array
    {
        switch (get_class($reflectionType)) {
            case Array_::class:
                return [self::convertDocumentorArrayToTypescriptType($reflectionType)];
            case ArrayShape::class:
                return [self::convertDocumentorArrayShapeToTypescriptType($reflectionType)];
            default:
                return self::convertPhpToTypescriptType((string) $reflectionType);
        }
    }

    private static function convertDocumentorArrayToTypescriptType(Array_ $reflectionType): string
    {
        // We use reflection to get the keyType because the property is protected and calling getKeyType() will return their default value instead of null
        $realKeyType = (new ReflectionClass(Array_::class))->getProperty('keyType')->getValue($reflectionType);
        $types = self::parseDecoratorType($reflectionType->getValueType());
        if ($realKeyType === null) {
            return 'Array<' . implode(' | ', $types) . '>';
        }
        return '{ '
            . '[key: ' . implode(' | ', self::parseDecoratorType($reflectionType->getKeyType())) . ']'
            . ': '
            . implode(' | ', $types)
            . ' }';
    }

    private static function convertDocumentorArrayShapeToTypescriptType(ArrayShape $arrayShape): string
    {
        $shape = '{ ';
        foreach ($arrayShape->getItems() as $item) {
            $key = $item->getKey();
            $value = self::parseDecoratorType($item->getValue());
            $shape .= $key . ': ' . implode(' | ', $value) . ', ';
        }
        $shape = rtrim($shape, ', ') . ' }';
        return $shape;
    }

    /**
     * @return array<TypeExpression>
     */
    public static function fromConstant(mixed $value): array
    {
        $types = [];
        foreach (self::convertPhpToTypescriptType(gettype($value)) as $typeName) {
            $type = new TypeExpression();
            $type->name = $typeName;
            $type->isCollection = false;
            $types[] = $type;
        }
        return $types;
    }

    /**
     * @return array<string>
     */
    private static function convertPhpToTypescriptType(string $phpTypes): array
    {
        $typeScriptTypes = [];
        if (strpos($phpTypes, '|')) {
            $phpTypes = explode('|', $phpTypes);
            foreach ($phpTypes as $phpType) {
                $typeScriptTypes[] = self::phpTypeNameToTypescript($phpType);
            }
            if (in_array('any', $typeScriptTypes)) {
                return ['any'];
            }
            return $typeScriptTypes;
        }
        return [self::phpTypeNameToTypescript($phpTypes)];
    }

    private static function phpTypeNameToTypescript(string $phpTypes): string
    {
        return match (trim($phpTypes)) {
            'int', 'float', 'double', 'integer' => 'number',
            'bool', 'boolean' => 'boolean',
            'string' => 'string',
            'null' => 'undefined',
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
