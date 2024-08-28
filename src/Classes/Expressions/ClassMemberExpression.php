<?php

namespace TsWink\Classes\Expressions;

use Illuminate\Support\Str;

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

    public static function tryParse(string $text, ?ClassMemberExpression &$result): bool
    {
        $classMember = new ClassMemberExpression();
        preg_match('/const +([a-zA-Z_]+[a-zA-Z0-9_]*) *\= *([0-9\.]+)/', $text, $matches);
        if (count($matches) > 1) {
            $classMember->name = $matches[1];
            $classMember->initialValue = $matches[2];
            $classMember->accessModifiers = "const";
            $classMember->type = new TypeExpression();
            $classMember->type->name = "number";
            $result = $classMember;
            return true;
        }
        preg_match('/\$([a-zA-Z_]+[a-zA-Z0-9_]*) *\= *[\'"]([^\'"]+)[\'"]/', $text, $matches);
        if (count($matches) > 1) {
            $classMember->name = $matches[1];
            $classMember->initialValue = $matches[2];
            $classMember->noConvert = true;
            $result = $classMember;
            return true;
        }
        preg_match('/function get([a-zA-Z0-9_]*)Attribute/', $text, $matches);
        if (count($matches) > 0) {
            $classMember->name = Str::camel($matches[1]);
            $result = $classMember;
            return true;
        }
        return false;
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
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
        if ($this->accessModifiers != "const" && !($this->type && $this->type->isCollection)) {
            $content .=  "?";
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
