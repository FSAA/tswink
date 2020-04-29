<?php

namespace TsWink\Classes\Expressions;

class ClassMemberExpression extends Expression
{
    /** @var string */
    public $name;

    /** @var string */
    public $access_modifiers;

    /** @var int */
    public $initial_value;

    /** @var string */
    public $type;

    /** @var bool */
    public $no_convert = false;

    public static function tryParse(string $text, ?ClassMemberExpression &$result): bool
    {
        $classMember = new ClassMemberExpression();
        preg_match('/const +([a-zA-Z_]+[a-zA-Z0-9_]*) *\= *([0-9]+)/', $text, $matches);
        if (count($matches) > 1) {
            $classMember->name = $matches[1];
            $classMember->initial_value = $matches[2];
            $classMember->access_modifiers = "const";
            $classMember->type = new TypeExpression();
            $classMember->type->name = "number";
            $result = $classMember;
            return true;
        }
        preg_match('/\$([a-zA-Z_]+[a-zA-Z0-9_]*) *\= *[\'"]([^\'"]+)[\'"]/', $text, $matches);
        if (count($matches) > 1) {
            $classMember->name = $matches[1];
            $classMember->initial_value = $matches[2];
            $classMember->no_convert = true;
            $result = $classMember;
            return true;
        }
        preg_match('/function get([a-zA-Z0-9_]*)Attribute/', $text, $matches);
        if (count($matches) > 0) {
            $classMember->name = camel_case($matches[1]);
            $result = $classMember;
            return true;
        }
        return false;
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        $content = "public ";
        if ($this->access_modifiers == "const") {
            $content .= "static readonly ";
        }
        $content .= $this->name . "?: ";
        if ($this->type) {
            $content .= $this->type->toTypeScript($options);
        } else {
            $content .= "any";
        }
        if ($this->initial_value != null) {
            $content .= " = " . $this->initial_value;
        }
        return $content;
    }
}
