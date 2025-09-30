<?php

namespace TsWink\Classes\Expressions;

class GenerationContext
{
    public bool $isForNewModel = false;
    public bool $useInterfaceInsteadOfClass = false;
    public bool $forcePropertiesOptional = false;

    /** @var array<string, string> Mapping of original type names to new model names */
    public array $typeNameMappings = [];

    public static function forRegularModel(ExpressionStringGenerationOptions $options): self
    {
        $context = new self();
        $context->useInterfaceInsteadOfClass = $options->useInterfaceInsteadOfClass;
        $context->forcePropertiesOptional = $options->forcePropertiesOptional;
        return $context;
    }

    public static function forNewModel(ExpressionStringGenerationOptions $options): self
    {
        $context = new self();
        $context->isForNewModel = true;
        $context->useInterfaceInsteadOfClass = $options->useInterfaceInsteadOfClass;
        $context->forcePropertiesOptional = true; // Always optional for new models
        return $context;
    }

    public function withTypeMapping(string $originalType, string $newType): self
    {
        $this->typeNameMappings[$originalType] = $newType;
        return $this;
    }

    public function getMappedTypeName(string $originalName): string
    {
        return $this->typeNameMappings[$originalName] ?? $originalName;
    }

    public function isForNewModel(): bool
    {
        return $this->isForNewModel;
    }
}
