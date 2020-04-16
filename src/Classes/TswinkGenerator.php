<?php

namespace TsWink\Classes;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use File;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

Class TswinkGenerator extends Generator
{
    /** @var TypeSimplifier */
    private $typeSimplifier;

    /** @var Table */
    private $table;

    /** @var string */
    private $destination;

    /** @var bool */
    private $allPropertiesNullable;

    /** @var bool */
    private $testForOptionalProperties;

    /** @var array */
    private $classesByTable;

    /** @var string */
    private $indentation;

    /** @var bool */
    private $skipMissingModels;

    public function __construct()
    {
        parent::__construct();

        $this->typeSimplifier = new TypeSimplifier;

        if(function_exists('config')) {
            $this->destination = base_path(config('tswink.ts_classes_destination'));
            $this->allPropertiesNullable = config('tswink.ts_all_properties_nullable');
            $this->testForOptionalProperties = config('tswink.ts_test_for_optional_properties');
            $this->classesByTable = $this->getModels();
            $this->indentation = config('tswink.ts_spaces_instead_of_tabs') ? str_repeat(' ', config('tswink.ts_indentation_number_of_spaces')) : "\t";
            $this->skipMissingModels = config('tswink.skip_missing_models') ? true : false;
        }
    }

    public function generate()
    {
        /** @var Table $table */
        foreach ($this->tables as $table) {
            $this->table = $table;
            $this->processTable();
        }
    }

    private function processTable()
    {
        $classContent = $this->getClassContent();
        if ($classContent !== null) {
            $this->writeFile($this->fileName() . ".ts", $classContent);
        }
    }

    private function fileName()
    {
        return $this->getTableFileName($this->table->getName());
    }

    private function getTableFileName($tableName)
    {
        return kebab_case(str_singular(camel_case($tableName)));
    }

    private function getClassContent()
    {
        $content = null;
        // Get the Model for the selected table
        $model = null;
        $hidden = [];
        if (isset($this->classesByTable[$this->table->getName()])) {
            $model = new $this->classesByTable[$this->table->getName()];
            // Load the hidden props
            $hidden = $model->getHidden();
        }

        if ($model || !$this->skipMissingModels) {
            // Generate the interface
            $tsClass = "export default interface {$this->getTableNameForClassFile()} {\n";
            foreach ($this->table->getColumns() as $column) {
                if (!$hidden || !in_array($column->getName(), $hidden)) {
                    $name = TswinkGenerator::escapeName($column->getName());
                    $type = $this->getSimplifiedType($column);
                    if ($this->allPropertiesNullable && $type !== 'any') {
                        $type .= ' | null';
                    }
                    $optionalProperty = (!$this->testForOptionalProperties || $column->getNotnull()) ? '' : '?';
                    $tsClass .= $this->indentation . "{$name}{$optionalProperty}: {$type};\n";
                }
            }

            // Take care of the relations
            $imports = '';
            $imported = [];
            if ($model && count($model->getModelRelations()) > 0) {
                $tsClass .= "\n\n";
                foreach ($model->getModelRelations() as $relation) {
                    if (!$hidden || !in_array($relation['relationName'], $hidden)) {
                        $targetModelName = substr($relation['targetClass'], strrpos($relation['targetClass'], '\\') + 1);
                        if ($relation['targetClass'] !== $this->classesByTable[$this->table->getName()]) {
                            $targetFileName = $this->getTableFileName(array_search($relation['targetClass'], $this->classesByTable));
                            if (!in_array($targetModelName, $imported)) {
                                $imported[] = $targetModelName;
                                $imports .= "import {$targetModelName} from './{$targetFileName}';\n";
                            }
                        }

                        $relationNameSnakeCase = Str::snake($relation['relationName']);
                        $isCollection = $relation['relationType'] === HasMany::class || $relation['relationType'] === BelongsToMany::class;
                        if ($isCollection) {
                            $tsClass .= $this->indentation . "{$relationNameSnakeCase}?: {$targetModelName}[] | { [key: string]: $targetModelName };\n";
                        } else {
                            $tsClass .= $this->indentation . "{$relationNameSnakeCase}?: {$targetModelName} | null;\n";
                        }
                    }
                }

                foreach ($model->getAppends() as $append) {
                    $tsClass .= $this->indentation . "{$append}?: any;\n";
                }
            }
            $content = $imports . "\n" . $tsClass . "}\n";
        }
        return $content;
    }

    private static function escapeName($name){
        if(strpos($name, "-") !== false){
            return "'$name'";
        }

        return $name;
    }

    private function getTableNameForClassFile()
    {
        return ucfirst($this->singularFromTableName());
    }

    private function singularFromTableName()
    {
        return str_singular(camel_case($this->table->getName()));
    }

    private function getSimplifiedType(Column $column)
    {
        return $this->typeSimplifier->simplify($column);
    }

    private function writeFile($fileName, $tsClass)
    {
        if(!file_exists($this->destination)) {
            mkdir($this->destination, 077, true);
        }

        $filePath = "{$this->destination}/$fileName";

        $file = fopen($filePath, "w");
        fwrite($file, $tsClass);
        fclose($file);
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    public function getModels()
    {
        $modelsClasses = [];
        $modelsPaths = config('tswink.models_paths');
        if (is_array($modelsPaths) && count($modelsPaths) > 0) {
            foreach ($modelsPaths as $modelsPath) {
                $files = File::files(base_path($modelsPath));
                foreach ($files as $file) {
                    if ($file->getType() === 'file' && strtolower($file->getExtension()) === 'php') {
                        $handle = fopen($file->getPathname(), 'r');
                        $namespace = null;
                        $className = null;
                        // Find the namespace and the class of every file in the folder
                        while (($namespace === null || $className === null) && ($buffer = fgets($handle)) !== false) {
                            preg_match('/namespace\\n* *(.+)\\n*;/', $buffer, $namespaceMatches);
                            if (count($namespaceMatches) > 0) {
                                $namespace = $namespaceMatches[1];
                            }
                            preg_match('/class\\n* *([a-zA-Z]+[a-zA-Z0-9]*)/', $buffer, $classMatches);
                            if (count($classMatches) > 0) {
                                $className = $classMatches[1];
                            }
                        }
                        fclose($handle);

                        if ($namespace && $className) {
                            $fullClassName = $namespace . '\\' . $className;
                            $model = new $fullClassName;
                            $modelsClasses[$model->getTable()] = $fullClassName;
                        }
                    }
                }
            }
        }

        return $modelsClasses;
    }
}
