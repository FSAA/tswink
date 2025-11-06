<?php

namespace TsWinkTests\Units;

use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Exception;
use Illuminate\Support\Facades\DB;

class TswinkGeneratorEnumServiceTest extends TestCase
{
    /** @var Connection */
    private $dbConnection;

    public function setup(): void
    {
        parent::setup();

        /** @var string[] $connectionConfig */
        $connectionConfig = DB::connection()->getConfig();
        $connectionParams = [];
        $driver = 'pdo_' . $connectionConfig['driver'];
        if (!in_array($driver, DriverManager::getAvailableDrivers())) {
            throw new Exception("Driver not supported: " . $driver);
        }
        /** @var key-of<DriverManager::DRIVER_MAP> $driver */
        $connectionParams['driver'] = $driver;
        $connectionParams['host'] = $connectionConfig['host'];
        $connectionParams['dbname'] = $connectionConfig['database'];
        $connectionParams['user'] = $connectionConfig['username'];
        $connectionParams['password'] = $connectionConfig['password'];
        $connectionParams['port'] = intval($connectionConfig['port']);
        $this->dbConnection = DriverManager::getConnection($connectionParams, new Configuration());
    }
    public function testEnumWithExportToTypescriptAttribute(): void
    {
        $sources = [__DIR__ . "/Input"];
        $classesDestination = __DIR__ . "/Output/Classes";
        $enumsDestination = __DIR__ . "/Output/Enums";

        $options = $this->createExpressionOptionsFromConfig();

        (new TswinkGenerator($this->dbConnection, true))->generate(
            $sources,
            $classesDestination,
            $enumsDestination,
            $options
        );

        // Test that enum file was generated
        $enumFile = $enumsDestination . "/Permissions.ts";
        $this->assertFileExists($enumFile);

        $content = file_get_contents($enumFile);
        $this->assertNotFalse($content, 'Failed to read generated Permissions.ts file');
        $this->assertNotEmpty($content);

        // Test basic enum structure
        $this->assertStringContainsString('export enum Permissions {', $content);
        $this->assertStringContainsString('ADMIN = 1,', $content);
        $this->assertStringContainsString('EDITOR = 2,', $content);
        $this->assertStringContainsString('VIEWER = 3,', $content);
        $this->assertStringContainsString('CONTRIBUTOR = 4,', $content);

        // Test service object generation
        $this->assertStringContainsString('export const PermissionsService = {', $content);

        // Test first method (using original method name)
        $this->assertStringContainsString('creationPermissions: () => {', $content);
        $this->assertStringContainsString('return [', $content);
        $this->assertStringContainsString('Permissions.ADMIN,', $content);
        $this->assertStringContainsString('Permissions.EDITOR', $content);

        // Test second method (using custom export name)
        $this->assertStringContainsString('managePermissions: () => {', $content);
        $this->assertStringContainsString('Permissions.ADMIN', $content);
    }

    public function testEnumWithoutExportToTypescriptAttribute(): void
    {
        // Test that regular enums don't generate service objects
        $sources = [__DIR__ . "/Input"];
        $classesDestination = __DIR__ . "/Output/Classes";
        $enumsDestination = __DIR__ . "/Output/Enums";

        $options = $this->createExpressionOptionsFromConfig();

        (new TswinkGenerator($this->dbConnection, true))->generate(
            $sources,
            $classesDestination,
            $enumsDestination,
            $options
        );

        // Test PhpNativeEnum (doesn't have ExportToTypescript methods)
        $enumFile = $enumsDestination . "/PhpNativeEnum.ts";
        $this->assertFileExists($enumFile);

        $content = file_get_contents($enumFile);
        $this->assertNotFalse($content, 'Failed to read generated PhpNativeEnum.ts file');

        // Should have enum but no service object
        $this->assertStringContainsString('export enum PhpNativeEnum {', $content);
        $this->assertStringNotContainsString('PhpNativeEnumService', $content);
    }

    public function testServiceMethodBodyParsing(): void
    {
        $sources = [__DIR__ . "/Input"];
        $classesDestination = __DIR__ . "/Output/Classes";
        $enumsDestination = __DIR__ . "/Output/Enums";

        $options = $this->createExpressionOptionsFromConfig();

        (new TswinkGenerator($this->dbConnection, true))->generate(
            $sources,
            $classesDestination,
            $enumsDestination,
            $options
        );

        $enumFile = $enumsDestination . "/Permissions.ts";
        $content = file_get_contents($enumFile);
        $this->assertNotFalse($content, 'Failed to read generated Permissions.ts file');

        // Verify that self::CASE references are converted to EnumName.CASE
        $this->assertStringNotContainsString('self::', $content);
        $this->assertStringContainsString('Permissions.ADMIN', $content);
        $this->assertStringContainsString('Permissions.EDITOR', $content);
    }

    public function testGenerationWithDifferentOptions(): void
    {
        $sources = [__DIR__ . "/Input"];
        $classesDestination = __DIR__ . "/Output/Classes";
        $enumsDestination = __DIR__ . "/Output/Enums";

        // Test with no semicolons
        $options = $this->createExpressionOptionsFromConfig();
        $options->useSemicolon = false;

        (new TswinkGenerator($this->dbConnection, true))->generate(
            $sources,
            $classesDestination,
            $enumsDestination,
            $options
        );

        $enumFile = $enumsDestination . "/Permissions.ts";
        $content = file_get_contents($enumFile);
        $this->assertNotFalse($content, 'Failed to read generated Permissions.ts file');

        // Should not have semicolons in service methods
        $this->assertStringContainsString('Permissions.ADMIN,', $content);
        $this->assertStringContainsString('Permissions.EDITOR', $content);
        $this->assertStringNotContainsString('];', $content);
    }
}
