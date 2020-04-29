<?php

namespace TsWink\Classes\Expressions;

class ClassExpression extends Expression
{
    /** @var string */
    public $namespace;

    /** @var ImportExpression[] */
    public $imports = [];

    /** @var string */
    public $non_auto_generated_imports;

    /** @var string */
    public $base_class_name;

    /** @var string */
    public $name;

    /** @var ClassMemberExpression[] */
    public $members = [];

    /** @var string */
    public $non_auto_generated_class_declarations;

    /** @var EloquentRelation[] */
    public $eloquent_relations = [];

    public static function tryParse(string $text, ?ClassExpression &$result): bool
    {
        $class = new ClassExpression();
        $matches = null;
        $line = strtok($text, "\r\n");
        while ($line !== false) {
            preg_match('/namespace\\n* *(.+)\\n*;/', $line, $matches);
            if (count($matches) > 0) {
                $class->namespace = $matches[1];
            }
            preg_match('/class\\n* *([a-zA-Z_]+[a-zA-Z0-9_]*) *extends *([a-zA-Z_]+[a-zA-Z0-9_]*)/', $line, $matches);
            if (count($matches) > 1) {
                $class->name = $matches[1];
                $class->base_class_name = $matches[2];
            }
            if (ClassMemberExpression::tryParse($line, $classMember)) {
                $class->members[$classMember->name] = $classMember;
            }
            $line = strtok("\r\n");
        }
        if ($class->name == null) {
            return false;
        }
        $classInstance = $class->instantiate();
        $class->eloquent_relations = self::parseEloquentRelations($classInstance);
        $result = $class;
        return true;
    }

    public function hasMember(string $name): bool
    {
        return array_key_exists($name, $this->members);
    }

    public function instantiate()
    {
        $fullyQualifiedClassName = $this->namespace . '\\' . $this->name;
        return new $fullyQualifiedClassName();
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        if ($this->base_class_name == "Enum") {
            return $this->toTypeScriptEnum($options);
        } else {
            return $this->toTypeScriptClass($options);
        }
    }

    /** @return EloquentRelation[] */
    private static function parseEloquentRelations($classInstance)
    {
        $relations = [];
        if (method_exists($classInstance, "getModelRelations") && count($classInstance->getModelRelations()) > 0) {
            foreach ($classInstance->getModelRelations() as $relation) {
                $relation = EloquentRelation::parse($relation);
                $relations[$relation->name] = $relation;
            }
        }
        return $relations;
    }

    private function toTypeScriptEnum(ExpressionStringGenerationOptions $options): string
    {
        $content = "export enum {$this->name} {\n";
        $enumContent = null;
        foreach ($this->members as $member) {
            $enumContent .= $member->name . " = " . $member->initial_value . ",\n";
        }
        $content .= $this->indent(trim($enumContent, "\n"), 1, $options) . "\n";
        $content .= "}";
        return $content;
    }

    private function toTypeScriptClass(ExpressionStringGenerationOptions $options): string
    {
        $content = null;
        foreach ($this->imports as $import) {
            $content .= $import->toTypeScript($options) . "\n";
        }
        $content .= "// <non-auto-generated-import-declarations>\n";
        $content .= $this->non_auto_generated_imports . "\n";
        $content .= "// </non-auto-generated-import-declarations>\n\n";
        $content .= "export default class {$this->name} {\n\n";
        $classBody = null;

        usort($this->members, fn ($a, $b) => strcmp($a->name, $b->name));
        foreach ($this->members as $member) {
            if (!$member->no_convert) {
                $classBody .= $member->toTypeScript($options) . ";\n";
            }
        }

        $classBody .= "\n";
        $classBody .= "constructor(init?: Partial<$this->name>) {\n";
        $constructorContent = "Object.assign(this, init);\n";
        foreach ($this->members as $member) {
            if ($member->type == null || $member->no_convert) {
                continue;
            } else if ($member->type->is_collection) {
                $constructorContent .= "init." . $member->name . " = init?." . $member->name . " ? Object.deserialize<" . $member->type->name . ">(init." . $member->name . ", " . $member->type->name . ") : undefined;\n";
            } else if (!$member->type->isPrimitive()) {
                $constructorContent .= "init." . $member->name . " = init?." . $member->name . " ? new " . $member->type->name . "(init." . $member->name . ") : undefined;\n";
            }
        }

        $classBody .= $this->indent(trim($constructorContent, "\n"), 1, $options) . "\n";
        $classBody .= "}\n\n";
        $classBody .= "// <non-auto-generated-class-declarations>\n";
        $classBody .= $this->indent($this->non_auto_generated_class_declarations, -1, $options) . "\n";
        $classBody .= "// </non-auto-generated-class-declarations>";
        $content .= $this->indent($classBody, 1, $options) . "\n";
        $content .= "}";
        return $content;
    }
}
