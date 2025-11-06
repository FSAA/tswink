<?php

declare(strict_types=1);

namespace TsWink\Classes\Expressions;

use ReflectionMethod;
use TsWink\Attributes\ExportToTypescript;

class ServiceMethodExpression extends Expression
{
    public string $methodName;
    public string $exportName;
    public string $enumName;
    /** @var string[] */
    public array $returnBody;

    public static function fromReflectionMethod(ReflectionMethod $method, string $enumName): ?ServiceMethodExpression
    {
        $attributes = $method->getAttributes(ExportToTypescript::class);
        if (empty($attributes)) {
            return null;
        }

        $serviceMethod = new ServiceMethodExpression();
        $serviceMethod->methodName = $method->getName();
        $serviceMethod->enumName = $enumName;

        $attribute = $attributes[0]->newInstance();
        $serviceMethod->exportName = $attribute->name ?? $method->getName();

        $serviceMethod->returnBody = self::executeMethodAndConvertToTypeScript($method, $enumName);

        return $serviceMethod;
    }

    /**
     * @return string[]
     */
    private static function executeMethodAndConvertToTypeScript(ReflectionMethod $method, string $enumName): array
    {
        $className = $method->getDeclaringClass()->getName();
        $methodName = $method->getName();

        // Execute the static method to get the actual enum cases
        try {
            $results = $className::{$methodName}();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to execute method {$className}::{$methodName}() marked with #[ExportToTypescript]: " . $e->getMessage(),
                0,
                $e
            );
        }

        if (!is_array($results)) {
            throw new \RuntimeException(
                "Method {$className}::{$methodName}() marked with #[ExportToTypescript] must return an array, got " . gettype($results)
            );
        }

        // Convert enum cases to TypeScript format
        $tsValues = [];
        foreach ($results as $index => $case) {
            if (!($case instanceof \UnitEnum)) {
                throw new \RuntimeException(
                    "Method {$className}::{$methodName}() marked with #[ExportToTypescript] must return an array of enum cases only. " .
                    "Found " . gettype($case) . " at index {$index}. Expected enum cases like self::CASE_NAME."
                );
            }

            // Validate that the enum case belongs to the current enum class
            if (!($case instanceof $className)) {
                $actualEnumClass = get_class($case);
                throw new \RuntimeException(
                    "Method {$className}::{$methodName}() marked with #[ExportToTypescript] returned enum case from wrong enum class. " .
                    "Found {$actualEnumClass}::{$case->name} at index {$index}, expected cases from {$className}."
                );
            }

            $tsValues[] = $enumName . '.' . $case->name;
        }

        return $tsValues;
    }

    public function toTypeScript(ExpressionStringGenerationOptions $options, GenerationContext $context): string
    {
        // Format the array with proper indentation
        $formattedArray = $this->formatArrayBody($options);

        return $this->exportName . ': () => {' . "\n" .
               $this->indent('return ' . $formattedArray . $this->generateSemicolon($options), 1, $options) . "\n" .
               '}';
    }

    private function formatArrayBody(ExpressionStringGenerationOptions $options): string
    {
        // Always format as multiline for consistency
        $arrayContent = "[\n";
        foreach ($this->returnBody as $value) {
            $arrayContent .= $this->indent($value, 1, $options) . ",\n";
        }
        $arrayContent .= "]";

        return $arrayContent;
    }

    private function generateSemicolon(ExpressionStringGenerationOptions $options): string
    {
        return $options->useSemicolon ? ';' : '';
    }
}
