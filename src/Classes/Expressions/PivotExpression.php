<?php

namespace TsWink\Classes\Expressions;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Illuminate\Support\Arr;
use TsWink\Classes\TypeConverter;

class PivotExpression extends Expression
{
    /** @var string */
    public string $tableName;

    /** @var string */
    public string $interfaceName;

    /** @var array<string, string> */
    public array $fields = [];

    /** @var array<string> */
    private array $requiredColumns = [];

    /**
     * Create a PivotExpression from an EloquentRelation with pivot information
     * @param EloquentRelation<\Illuminate\Database\Eloquent\Model> $relation
     * @param Table[] $tables
     * @param TypeConverter $typeConverter
     */
    public static function fromEloquentRelation(EloquentRelation $relation, array $tables, TypeConverter $typeConverter): ?PivotExpression
    {
        if ($relation->pivotAccessor === null || $relation->pivotTable === null) {
            return null;
        }

        $pivot = new PivotExpression();
        $pivot->tableName = $relation->pivotTable;
        $pivot->interfaceName = self::tableNameToInterfaceName($relation->pivotTable);

        // Find the pivot table in the database
        $pivotTable = self::findTableByName($tables, $relation->pivotTable);
        if (!$pivotTable) {
            throw new Exception("Error: Pivot table '{$relation->pivotTable}' not found in database from relation '{$relation->name}'\n");
        }

        // If no pivot columns are specified, use all non-foreign-key columns from the pivot table
        $columnsToProcess = empty($relation->pivotColumns)
            ? self::getDefaultPivotColumns($pivotTable)
            : $relation->pivotColumns;

        // Extract field types from the pivot columns and identify required columns
        foreach ($columnsToProcess as $columnName) {
            if (!$pivotTable->hasColumn($columnName)) {
                throw new Exception("Column '{$columnName}' not found in pivot table '{$relation->pivotTable}' from relation '{$relation->name}");
            }
            $column = $pivotTable->getColumn($columnName);
            $pivot->fields[$columnName] = $typeConverter->convert($column);

            // Track required (non-nullable) columns that are present in this relation
            if ($column->getNotnull()) {
                // Column is not nullable, so it's required
                $pivot->requiredColumns[] = $columnName;
            }
        }

        return $pivot;
    }

    /**
     * Find a table by name in the tables array
     * @param Table[] $tables
     */
    private static function findTableByName(array $tables, string $tableName): ?Table
    {
        foreach ($tables as $table) {
            if ($table->getName() === $tableName) {
                return $table;
            }
        }
        return null;
    }

    /**
     * Get default pivot columns by filtering out foreign keys and common Laravel columns
     * @return array<string>
     */
    private static function getDefaultPivotColumns(Table $pivotTable): array
    {
        /** @var array<string> $allColumns */
        $allColumns = Arr::pluck($pivotTable->getColumns(), fn (Column $column) => $column->getName());

        // Filter out common foreign key columns (id, created_at, updated_at, etc.)
        $excludeColumns = ['id', 'created_at', 'updated_at'];

        return array_filter($allColumns, function (string $columnName) use ($excludeColumns): bool {
            // Exclude foreign key columns (typically ending with _id) and timestamp columns
            if (in_array($columnName, $excludeColumns) || str_ends_with($columnName, '_id')) {
                return false;
            }
            return true;
        });
    }

    /**
     * Convert pivot table name to TypeScript interface name
     */
    private static function tableNameToInterfaceName(string $tableName): string
    {
        // Convert snake_case to PascalCase and add Pivot suffix
        $parts = explode('_', $tableName);
        $interfaceName = '';
        foreach ($parts as $part) {
            $interfaceName .= ucfirst($part);
        }
        return $interfaceName . 'Pivot';
    }

    /**
     * Generate the TypeScript interface definition
     */
    public function toTypeScript(ExpressionStringGenerationOptions $options): string
    {
        $content = "// Auto-generated pivot interface for table: {$this->tableName}\n";
        $content .= "export default interface {$this->interfaceName} {\n";

        foreach ($this->fields as $fieldName => $fieldType) {
            $optional = '?'; // All optional since we don't know if it's included in the pivot for that specific query
            $semicolon = $options->useSemicolon ? ';' : '';
            $indentExpression = $this->getIndentExpression($options);
            $content .= "{$indentExpression}{$fieldName}{$optional}: {$fieldType}{$semicolon}\n";
        }

        $content .= "}\n";
        return $content;
    }

    /**
     * Get the filename for this pivot interface
     */
    public function getFileName(): string
    {
        return $this->interfaceName . '.ts';
    }

    /**
     * Check if this pivot has any fields
     */
    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    /**
     * Get the required (non-nullable) columns for this pivot
     * @return array<string>
     */
    public function getRequiredColumns(): array
    {
        return $this->requiredColumns;
    }
}
