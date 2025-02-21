<?php

namespace TsWink\Classes;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use TsWink\Classes\Expressions\ClassExpression;
use TsWink\Classes\Expressions\ClassMemberExpression;
use TsWink\Classes\Expressions\EnumExpression;
use TsWink\Classes\Expressions\ImportExpression;
use TsWink\Classes\Expressions\TypeExpression;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use TsWink\Classes\Utils\StringUtils;

class TswinkGenerator
{
    private TypeConverter $typeConverter;

    /** @var Table[] */
    protected array $tables;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    protected AbstractSchemaManager $schemaManager;

    public function __construct(Connection $dbConnection)
    {
        $this->typeConverter = new TypeConverter();

        $this->schemaManager = $dbConnection->createSchemaManager();
        $this->tables = $this->schemaManager->listTables();
    }

    /** @param string[] $sources*/
    public function generate($sources, string $classesDestination, string $enumsDestination, ExpressionStringGenerationOptions $codeGenerationOptions): void
    {
        if (is_array($sources) && count($sources) > 0) {
            foreach ($sources as $enumsPath) {
                $files = scandir($enumsPath);
                if ($files === false) {
                    continue;
                }
                foreach ($files as $file) {
                    echo("Processing '" . $file . "'...\n");
                    if (pathinfo(strtolower($file), PATHINFO_EXTENSION) == 'php') {
                        $this->convertFile($enumsPath . "/" . $file, $classesDestination, $enumsDestination, $codeGenerationOptions);
                    }
                }
            }
        }
    }

    public function convertFile(string $filePath, string $classesDestination, string $enumsDestination, ExpressionStringGenerationOptions $codeGenerationOptions): void
    {
        $class = null;
        $fileContent = file_get_contents($filePath);
        if ($fileContent && (ClassExpression::tryParse($fileContent, $class) || EnumExpression::tryParse($fileContent, $class))) {
            $fileName = $this->resolveDestination($class, $enumsDestination, $classesDestination);
            if ($class->baseClassName != "Enum") {
                if (!$codeGenerationOptions->useInterfaceInsteadOfClass) {
                    $this->addUuidToClass($class);
                }
                $this->addPhpQualifiedClassName($class);
                $this->mergeDatabaseSchema($class);
                $class->generateMembersFromDocBlock();
            }
            $fileName .= "/" . $class->name . ".ts";
            $this->mergeNonAutoGeneratedDeclarations($class, $fileName);
            $this->writeFile($fileName, $class->toTypeScript($codeGenerationOptions));
        }
    }

    private function resolveDestination(ClassExpression $class, string $enumsDestination, string $classesDestination): string
    {
        if ($class->baseClassName == "Enum") {
            return $enumsDestination;
        }
        return $classesDestination;
    }

    private function addPhpQualifiedClassName(ClassExpression $class): void
    {
        $classMember = new ClassMemberExpression();
        $classMember->name = 'phpQualifiedClassName';
        $classMember->accessModifiers = 'const';
        $classMember->initialValue = "'" . addSlashes($class->fullyQualifiedClassName()) . "'";
        $type = new TypeExpression();
        $type->name = 'string';
        $type->isCollection = false;
        $classMember->type = $type;
        $class->members[] = $classMember;
    }

    private function addUuidToClass(ClassExpression $class): void
    {
        $uuidImport = new ImportExpression();
        $uuidImport->name = "{ v4 as uuid }";
        $uuidImport->target = "uuid";
        array_push($class->imports, $uuidImport);

        $uuidClassMember = new ClassMemberExpression();
        $uuidClassMember->name = "uuid";
        $uuidClassMember->accessModifiers = "public";
        $uuidClassMember->initialValue = "uuid()";
        $uuidType = new TypeExpression();
        $uuidType->name = "string";
        $uuidType->isCollection = false;
        $uuidClassMember->type = $uuidType;
        array_push($class->members, $uuidClassMember);
    }

    public function mergeDatabaseSchema(ClassExpression $class): void
    {
        $instance = $class->instantiate();
        if (!method_exists($instance, "getTable")) {
            return;
        }
        $table = current(array_filter($this->tables, function ($table) use ($instance) {
            return $table->getName() == $instance->getTable();
        }));
        if ($table === false) {
            return;
        }
        foreach ($table->getColumns() as $column) {
            $classMember = new ClassMemberExpression();
            $classMember->name = $column->getName();
            $classMember->type = new TypeExpression();
            $classMember->type->name = $this->typeConverter->convert($column);
            $classMember->isOptional = !$column->getNotnull();
            $class->members[$classMember->name] = $classMember;
        }
        foreach ($class->eloquentRelations as $relation) {
            $classMember = new ClassMemberExpression();
            $classMember->name = Str::snake($relation->name);
            $classMember->type = new TypeExpression();
            $classMember->type->name = $relation->targetClassName;
            $classMember->type->isCollection = $relation->type === HasMany::class || $relation->type === HasManyThrough::class || $relation->type === BelongsToMany::class;
            if ($relation->targetClassName != $class->name) {
                $tsImport = new ImportExpression();
                $tsImport->name = $relation->targetClassName;
                $tsImport->target = "./" . $relation->targetClassName;
                $class->imports[$tsImport->name] = $tsImport;
            }
            $class->members[$classMember->name] = $classMember;
        }
    }

    private function mergeNonAutoGeneratedDeclarations(ClassExpression $class, string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }
        $fileContent = file_get_contents($filePath);
        if (!$fileContent) {
            return;
        }
        $imports = StringUtils::textBetween($fileContent, "// <non-auto-generated-import-declarations>", "// </non-auto-generated-import-declarations>");
        if ($imports) {
            $class->nonAutoGeneratedImports = trim(str_replace("\r", "", $imports), "\n");
        }
        $declarations = StringUtils::textBetween($fileContent, "// <non-auto-generated-class-declarations>", "// </non-auto-generated-class-declarations>");
        if ($declarations) {
            $class->nonAutoGeneratedClassDeclarations = trim(str_replace("\r", "", $declarations), "\n");
        }
    }

    private function writeFile(string $filePath, string $content): void
    {
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0770, true);
        }
        file_put_contents($filePath, $content);
    }
}
