<?php

namespace TsWink\Classes\Expressions;

use ReflectionEnumUnitCase;
use ReflectionMethod;
use ReflectionNamedType;

class ClassMemberExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $accessModifiers;

    /** @var string */
    public $initialValue;

    /** @var ?TypeExpression */
    public $type;

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
        $classMember->type = TypeExpression::fromReflectionMethod($method);
        $classMember->isOptional = true; // We can't be sure it was appended
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
        $classMember->type = TypeExpression::fromConstant($value);
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
        $classMember->type = TypeExpression::fromReflectionNamedType($type);
        $classMember->isOptional = false;
        return $classMember;
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($options->useInterfaceInsteadOfClass && $this->accessModifiers == "const") {
            return '';
        }

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
        if ($this->accessModifiers != "const" && !($this->type && $this->type->isCollection)) {
            $content .= ($options->forcePropertiesOptional || $this->isOptional) ? "?" : '';
        }
        $content .= ": ";
        $content .= $this->resolveType($options);
        if (
            !$options->useInterfaceInsteadOfClass
            && $this->initialValue != null
        ) {
            $content .= " = " . $this->initialValue;
        }
        return $content;
    }

    public function resolveType(ExpressionStringGenerationOptions $options): string
    {
        if ($this->type) {
            return $this->type->toTypeScript($options);
        }
        return "any";
    }
}
