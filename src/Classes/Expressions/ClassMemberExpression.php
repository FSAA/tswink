<?php

namespace TsWink\Classes\Expressions;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use ReflectionEnumUnitCase;
use ReflectionMethod;
use ReflectionNamedType;
use TsWink\Classes\TypeConverter;

class ClassMemberExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $accessModifiers;

    /** @var string */
    public $initialValue;

    /** @var ?array<TypeExpression> */
    public $types;

    /** @var bool */
    public $noConvert = false;

    public bool $isOptional = true;

    public static function fromReflectionMethod(ReflectionMethod $method): ?ClassMemberExpression
    {
        $nameMatches = [];
        preg_match('/^get([a-zA-Z0-9_]+)Attribute$/', $method->name, $nameMatches);
        if (count($nameMatches) !== 2) {
            return null;
        }
        $classMember = new ClassMemberExpression();
        $classMember->name = lcfirst($nameMatches[1]);
        $classMember->accessModifiers = "public";
        $classMember->types = TypeExpression::fromReflectionMethod($method);
        $classMember->isOptional = true; // We can't be sure it was added to the model with "append"
        return $classMember;
    }

    public static function fromConstant(string $name, mixed $value): ?ClassMemberExpression
    {
        $classMember = new ClassMemberExpression();
        $classMember->name = $name;
        $constantValue = json_encode($value);
        if ($constantValue === false) {
            return null;
        }
        $classMember->initialValue = $constantValue;
        $classMember->accessModifiers = "const";
        $classMember->types = TypeExpression::fromConstant($value);
        $classMember->isOptional = false;
        return $classMember;
    }

    public static function fromCase(ReflectionEnumUnitCase $case, ?ReflectionNamedType $type): ?ClassMemberExpression
    {
        if (!$type) {
            return null;
        }
        $classMember = new ClassMemberExpression();
        $classMember->name = $case->getName();
        $constantValue = json_encode($case->getValue());
        if ($constantValue === false) {
            return null;
        }
        $classMember->initialValue = $constantValue;
        $classMember->accessModifiers = "const";
        $classMember->types = TypeExpression::fromReflectionNamedType($type);
        $classMember->isOptional = false;
        return $classMember;
    }

    /**
     * @param ImportExpression[] $classImports
     */
    public static function fromDocBlock(Property|PropertyRead|PropertyWrite $propertyTag, array $classImports): ?ClassMemberExpression
    {
        $variableName = $propertyTag->getVariableName();
        if (!$variableName || !self::isDocBlockNameUsable($variableName)) {
            return null;
        }

        $classMember = new ClassMemberExpression();
        $classMember->name = $variableName;
        $classMember->types = TypeExpression::fromPropertyDecorator($propertyTag, $classImports);
        $classMember->setIsOptionalFromTypes();
        return $classMember;
    }

    private static function isDocBlockNameUsable(string $propertyName): bool
    {
        $validVariableNamePatterns = [
            '/^[a-zA-Z_][a-zA-Z0-9_]*_count$/',
        ];
        return !!Arr::first($validVariableNamePatterns, function ($pattern) use ($propertyName) {
            return !!preg_match($pattern, $propertyName);
        });
    }

    public static function fromColumn(Column $column, TypeConverter $typeConverter): ClassMemberExpression
    {
        $classMember = new ClassMemberExpression();
        $classMember->name = $column->getName();
        $type = new TypeExpression();
        $type->name = $typeConverter->convert($column);
        $classMember->types = [$type];
        $classMember->isOptional = !$column->getNotnull();
        return $classMember;
    }

    /**
     * @param EloquentRelation<Model> $relation
     */
    public static function fromRelation(EloquentRelation $relation, ClassExpression &$class): ClassMemberExpression
    {
        $typeScriptModelType = $relation->classNameToTypeScriptType();

        $classMember = new ClassMemberExpression();
        $classMember->name = Str::snake($relation->name);
        $type = new TypeExpression();
        $type->name = $typeScriptModelType;
        $type->isCollection = self::isRelationCollection($relation->type);
        $classMember->types = [$type];
        if ($typeScriptModelType != $class->name) {
            $tsImport = new ImportExpression();
            $tsImport->name = $typeScriptModelType;
            $tsImport->target = "./" . $typeScriptModelType;
            $class->imports[$tsImport->name] = $tsImport;
        }
        return $classMember;
    }

    /**
     * @param class-string $relationType
     */
    private static function isRelationCollection(string $relationType): bool
    {
        $collectionRelations = [
            HasMany::class,
            HasManyThrough::class,
            BelongsToMany::class,
            MorphToMany::class,
        ];

        return !!Arr::first($collectionRelations, function ($collectionRelation) use ($relationType) {
            return $relationType === $collectionRelation; // Do not check with if subclass_of abstract, HasOne extends HasOneOrMany, which is not a collection
        });
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        $content = '';
        $content .= $this->resolveKeywords($options);
        $content .= ": ";
        $content .= $this->resolveTypeForTypeScript($options);
        if (
            !$options->useInterfaceInsteadOfClass
            && $this->initialValue != null
        ) {
            $content .= " = " . $this->convertToTypeScriptValue($this->initialValue);
        }
        return $content;
    }

    public function toTypeScriptConstant(): string {
        if ($this->initialValue === null) {
            return '';
        }
        $content = $this->name . ': ' . $this->convertToTypeScriptValue($this->initialValue);
        return $content;
    }

    public function convertToTypeScriptValue(string $value): string
    {
        // Convert all double-quoted strings to single-quoted strings
        // This handles both simple strings and strings inside arrays/objects
        $result = preg_replace_callback('/"([^"\\\\]*(\\\\.[^"\\\\]*)*)"/', function($matches) {
            // Extract the content inside the double quotes
            $innerValue = $matches[1];
            // Escape any single quotes and convert backslash-escaped double quotes back to double quotes
            $escapedValue = str_replace(["'", '\\"'], ["\\'", '"'], $innerValue);
            return "'" . $escapedValue . "'";
        }, $value);

        return $result !== null ? $result : $value;
    }

    private function resolveKeywords(ExpressionStringGenerationOptions $options): string
    {
        $content = '';
        if (!$options->useInterfaceInsteadOfClass) {
            $content = "public ";
        }
        if ($this->accessModifiers == "const") {
            if (!$options->useInterfaceInsteadOfClass) {
                $content = "static ";
            }
            $content .= "readonly ";
        }
        $content .= $this->name;
        $content .= $this->resolveOptionalFlag($options);
        return $content;
    }

    private function resolveOptionalFlag(ExpressionStringGenerationOptions $options): string
    {
        if ($this->accessModifiers === "const") {
            // const always have an initial value, so they cannot be optional.
            return '';
        }
        if ($this->isOnlyCollectionType() && !$options->useInterfaceInsteadOfClass) {
            // If it's a collection type and not an interface, we don't add the optional flag because the constructor will always initialize it.
            return '';
        }
        if ($options->forcePropertiesOptional) {
            return '?';
        }
        return $this->isOptional ? '?' : '';
    }

    private function isOnlyCollectionType(): bool
    {
        return $this->types && count($this->types) === 1 && $this->types[0]->isCollection;
    }

    public function resolveTypeForTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($this->types) {
            return array_reduce($this->types, function (string $carry, TypeExpression $type) use ($options) {
                if ($type->name === 'undefined') {
                    return $carry;
                }
                return $carry . ($carry ? ' | ' : '') . $type->toTypeScript($options);
            }, '');
        }
        return "any";
    }

    public function setIsOptionalFromTypes(): bool
    {
        if (!$this->types) {
            throw new Exception("Types not set");
        }
        $this->isOptional = !!Arr::first($this->types, function (TypeExpression $type) {
            return $type->name === 'undefined';
        });
        return $this->isOptional;
    }

    public function canGenerateInBody(ExpressionStringGenerationOptions $options): bool
    {
        if ($options->useInterfaceInsteadOfClass && $this->accessModifiers == "const") {
            return false;
        }
        return true;
    }
}
