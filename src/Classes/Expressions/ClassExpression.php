<?php

namespace TsWink\Classes\Expressions;

use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;

class ClassExpression extends Expression
{
    /** @var string */
    public $namespace;

    /** @var ImportExpression[] */
    public $imports = [];

    /** @var string */
    public $nonAutoGeneratedImports;

    /** @var string */
    public $baseClassName;

    /** @var string */
    public $name;

    /** @var ClassMemberExpression[] */
    public $members = [];

    /** @var string */
    public $nonAutoGeneratedClassDeclarations;

    /** @var EloquentRelation<Model>[] */
    public $eloquentRelations = [];

    public ?string $extends = null;

    public static function tryParse(string $text, ?ClassExpression &$result, ExpressionStringGenerationOptions $codeGenerationOptions): bool
    {
        $class = new ClassExpression();
        $matches = null;
        $line = strtok($text, "\r\n");
        while ($line !== false) {
            preg_match('/namespace\\n* *(.+)\\n*;/', $line, $matches);
            if (isset($matches[1])) {
                $class->namespace = $matches[1];
            }
            preg_match('/class\\n* *([a-zA-Z_]+[a-zA-Z0-9_]*) *extends *([a-zA-Z_]+[a-zA-Z0-9_]*)/', $line, $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $class->name = $matches[1];
                $class->baseClassName = $matches[2];
                break;
            }
            $line = strtok("\r\n");
        }
        if ($class->name == null) {
            return false;
        }
        self::processClassMembers($class);
        if ($class->baseClassName != "Enum") {
            /** @var class-string<Model> $className */
            $className = $class->fullyQualifiedClassName();
            $class->eloquentRelations = self::parseEloquentRelations($className);

            if ($class->name !== 'BaseModel') {
                $baseModelImport = new ImportExpression();
                $baseModelImport->name = 'BaseModel';
                $baseModelImport->target = './BaseModel';
                $class->extends = 'BaseModel';
                if ($codeGenerationOptions->createSeparateClassForNewModels) {
                    $baseModelImport->name = 'New' . ucfirst($class->name);
                    $baseModelImport->target = './' . $baseModelImport->name;
                    $class->extends = $baseModelImport->name;
                }
                $class->imports[] = $baseModelImport;
            }
        }
        $result = $class;
        return true;
    }

    private static function processClassMembers(ClassExpression &$class): void
    {
        $reflectionClass = new ReflectionClass($class->fullyQualifiedClassName());
        $parentMethods = [];
        if ($reflectionClass->getParentClass()) {
            $parentMethods = $reflectionClass->getParentClass()->getMethods(ReflectionMethod::IS_PUBLIC);
        }
        foreach (array_diff($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC), $parentMethods) as $method) {
            $classMember = ClassMemberExpression::fromReflectionMethod($method);
            if ($classMember) {
                $class->members[$classMember->name] = $classMember;
            }
        }
        $parentConstants = [];
        if ($reflectionClass->getParentClass()) {
            $parentConstants = $reflectionClass->getParentClass()->getConstants();
        }
        foreach ($reflectionClass->getConstants() as $name => $value) {
            if (array_key_exists($name, $parentConstants) && $value === $parentConstants[$name]) {
                continue; // Skip constants inherited from parent class if they are the same
            }
            $classMember = ClassMemberExpression::fromConstant($name, $value);
            if ($classMember) {
                $class->members[$classMember->name] = $classMember;
            }
        }
    }

    public function hasMember(string $name): bool
    {
        return array_key_exists($name, $this->members);
    }

    /**
     * @return class-string
     */
    public function fullyQualifiedClassName(): string
    {
        $className = $this->namespace . '\\' . $this->name;
        if (class_exists($className)) {
            return $className;
        }
        throw new Exception("Class $className not found");
    }

    public function instantiate(): object
    {
        $fullyQualifiedClassName = $this->fullyQualifiedClassName();
        return new $fullyQualifiedClassName();
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($this->baseClassName == "Enum") {
            return $this->toTypeScriptEnum($options);
        }
        return $this->toTypeScriptClass($options);
    }

    /**
     * @param class-string<Model> $className
     * @return EloquentRelation<Model>[]
     * */
    private static function parseEloquentRelations(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $relations = [];
        $classInstance = new $className();
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $eloquentRelation = self::getMethodEloquentRelationName($method);
            if (!$eloquentRelation) {
                continue;
            }

            /** @var Relation<Model,Model,Model> $relationResult */
            $relationResult = $method->invoke($classInstance);
            $relation = EloquentRelation::parse([
                'relationName' => $method->getName(),
                'targetClass' => $relationResult->getRelated()::class,
                'relationType' => $eloquentRelation
            ]);
            $relations[$relation->name] = $relation;
        }
        return $relations;
    }

    /**
     * @return class-string<Relation<Model,Model,Collection<int,Model>|Model>>|null
     */
    private static function getMethodEloquentRelationName(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();
        if (!$returnType instanceof ReflectionNamedType || $returnType->isBuiltin()) {
            return null;
        }

        $returnTypeName = $returnType->getName();
        if (!class_exists($returnTypeName)) {
            return null;
        }
        if (!is_subclass_of($returnTypeName, Relation::class)) {
            return null;
        }
        return $returnTypeName;
    }

    public function generateMembersFromDocBlock(): void
    {
        $reflectionClass = new ReflectionClass($this->fullyQualifiedClassName());
        $doc = $reflectionClass->getDocComment();
        if (!$doc) {
            return;
        }

        $docBlockFactory = DocBlockFactory::createInstance();
        $docBlock = $docBlockFactory->create($doc);

        /** @var Array<Property|PropertyRead> $propertyTags */
        $propertyTags = array_merge(
            $docBlock->getTagsWithTypeByName('property'),
            $docBlock->getTagsWithTypeByName('property-read'),
        );

        // Handle custom tswink-property tags
        $tswinkPropertyTags = $docBlock->getTagsByName('tswink-property');

        foreach ($propertyTags as $propertyTag) {
            $this->processPropertyTag($propertyTag);
        }

        foreach ($tswinkPropertyTags as $tswinkPropertyTag) {
            if (get_class($tswinkPropertyTag) !== Generic::class) {
                continue; // Skip if not a Generic tag
            }
            $this->processTswinkPropertyTag($tswinkPropertyTag);
        }
    }

    private function processPropertyTag(Property|PropertyRead|PropertyWrite $propertyTag): void
    {
        $member = $this->members[$propertyTag->getVariableName()] ?? null;
        if (!$member) {
            $member = ClassMemberExpression::fromDocBlock($propertyTag, $this->imports);
            if ($member) {
                $this->members[$member->name] = $member;
            }
            return;
        }
        $member->types = TypeExpression::fromPropertyDecorator($propertyTag, $this->imports);
    }

    private function processTswinkPropertyTag(Generic $tswinkPropertyTag): void
    {
        // Parse the tswink-property tag description to extract type and variable name
        $description = (string) $tswinkPropertyTag->getDescription();

        // Expected format: "type $variableName"
        if (preg_match('/^(.+?)\s+\$(\w+)(?:\s+(.*))?$/', $description, $matches)) {
            $typeString = $matches[1];
            $variableName = $matches[2];
            $description = $matches[3] ?? '';

            // Create a synthetic property tag for compatibility
            $syntheticPropertyTag = $this->createSyntheticPropertyTag($typeString, $variableName, $description);
            if ($syntheticPropertyTag) {
                $this->processPropertyTag($syntheticPropertyTag);
            }
        }
    }

    private function createSyntheticPropertyTag(string $typeString, string $variableName, string $description): ?Property
    {
        try {
            $contextFactory = new ContextFactory();
            $context = $contextFactory->createFromReflector(new ReflectionClass($this->fullyQualifiedClassName()));

            // Create a synthetic docblock with just the property tag
            $syntheticDocBlock = "/**\n * @property $typeString \$$variableName $description\n */";
            $docBlockFactory = DocBlockFactory::createInstance();
            $docBlock = $docBlockFactory->create($syntheticDocBlock, $context);

            $propertyTags = $docBlock->getTagsWithTypeByName('property');
            if (!isset($propertyTags[0]) || get_class($propertyTags[0]) !== Property::class) {
                return null;
            }
            return $propertyTags[0];
        } catch (Exception $e) {
            // If parsing fails, return null
            return null;
        }
    }

    private function toTypeScriptEnum(ExpressionStringGenerationOptions $options): string
    {
        $content = "export enum {$this->name} {\n";
        $enumContent = null;
        foreach ($this->members as $member) {
            if ($member->noConvert) {
                continue;
            }
            $enumContent .= $member->name . " = " . $member->initialValue . ",\n";
        }
        if ($enumContent) {
            $content .= $this->indent(trim($enumContent, "\n"), 1, $options) . "\n";
        }
        $content .= "}\n";
        return $content;
    }

    private function toTypeScriptClass(ExpressionStringGenerationOptions $options): string
    {
        $extends = $this->extends ? " extends " . $this->extends : '';
        $content = null;
        foreach ($this->imports as $import) {
            $content .= $import->toTypeScript($options) . "\n";
        }
        $content .= $this->generateNonAutoGeneratedClassDeclarations();
        $content .= $this->generateExportStatement($options, $extends);
        $classBody = '';

        usort($this->members, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });
        foreach ($this->members as $member) {
            if (!$member->noConvert) {
                $memberTypescript = $member->toTypeScript($options);
                if (!$memberTypescript) {
                    continue;
                }
                $classBody .= $memberTypescript . $this->generateSemicolon($options) . "\n";
            }
        }

        $classBody .= "\n";
        if (!$options->useInterfaceInsteadOfClass) {
            $classBody .= $this->generateConstructor($options, $extends);
        }
        $isOnlyComment = trim($classBody) === '';
        $classBody .= "// <non-auto-generated-class-declarations>\n";
        if (trim($this->nonAutoGeneratedClassDeclarations)) {
            $classBody .= "\n" . $this->indent($this->nonAutoGeneratedClassDeclarations, -1, $options) . "\n";
        }
        $classBody .= "\n";
        $classBody .= "// </non-auto-generated-class-declarations>";
        $content .= ($isOnlyComment ? "\n" : '') . $this->indent($classBody, 1, $options) . "\n";
        $content .= "}\n";
        return $content;
    }

    private function generateExportStatement(ExpressionStringGenerationOptions $options, string $extends): string
    {
        if ($options->useInterfaceInsteadOfClass) {
            return "export default interface {$this->name}{$extends} {\n";
        }
        return "export default class {$this->name}{$extends} {\n\n";
    }

    private function generateNonAutoGeneratedClassDeclarations(): string
    {
        $content = "\n";
        $content .= "// <non-auto-generated-import-declarations>\n";
        $content .= $this->nonAutoGeneratedImports . "\n";
        $content .= "// </non-auto-generated-import-declarations>\n\n";
        return $content;
    }

    private function generateSemicolon(ExpressionStringGenerationOptions $options): string
    {
        return $options->useSemicolon ? ';' : '';
    }

    private function generateConstructor(ExpressionStringGenerationOptions $options, string $extends): string
    {
        $constructor = "constructor(init?: Partial<$this->name>) {\n";
        $constructorContent = '';
        if ($extends) {
            $constructorContent .= "super(init);\n";
        }
        $constructorContent .= "Object.assign(this, init);\n";
        foreach ($this->members as $member) {
            if ($member->types == null || $member->noConvert || count($member->types) > 1) {
                continue;
            } elseif ($member->types[0]->isCollection) {
                $constructorContent .= "this." . $member->name . " = init?." . $member->name . " ? init." . $member->name . ".map(v => new " . $member->types[0]->name . "(v)) : []" . $this->generateSemicolon($options) . "\n";
            } elseif ($member->types[0]->name == "Date") {
                $constructorContent .= "this." . $member->name . " = init?." . $member->name . " ? Date.parseEx(init." . $member->name . ") : undefined" . $this->generateSemicolon($options) . "\n";
            } elseif (!$member->types[0]->isPrimitive()) {
                $constructorContent .= "this." . $member->name . " = init?." . $member->name . " ? new " . $member->types[0]->name . "(init." . $member->name . ") : undefined" . $this->generateSemicolon($options) . "\n";
            }
        }

        $constructor .= $this->indent(trim($constructorContent, "\n"), 1, $options) . "\n";
        $constructor .= "}\n\n";
        return $constructor;
    }
}
