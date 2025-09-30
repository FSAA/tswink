<?php

declare(strict_types=1);

namespace TsWinkTests\Units;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;

/**
 * Test TswinkGenerator with different configuration options
 */
class TswinkGeneratorConfigurationTest extends TestCase
{
    private Connection $dbConnection;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the same database connection setup as comprehensive test
        $defaultConnection = config('database.default');
        if (!is_string($defaultConnection)) {
            throw new \RuntimeException('Default database connection must be a string');
        }

        $connectionConfig = config('database.connections.' . $defaultConnection);

        if (!is_array($connectionConfig)) {
            throw new \RuntimeException('Database connection config is not an array');
        }

        $driverName = $connectionConfig['driver'] ?? 'sqlite';
        if (!is_string($driverName)) {
            throw new \RuntimeException('Driver name must be a string');
        }

        /** @var 'pdo_sqlite'|'pdo_mysql'|'pdo_pgsql'|'pdo_sqlsrv' $driver */
        $driver = $driverName === 'sqlite' ? 'pdo_sqlite' : 'pdo_' . $driverName;

        if ($driverName === 'sqlite') {
            $database = $connectionConfig['database'] ?? '';
            if (!is_string($database)) {
                throw new \RuntimeException('Database path must be a string');
            }
            $connectionParams = [
                'path' => $database,
                'driver' => $driver,
            ];
        } else {
            $host = $connectionConfig['host'] ?? 'localhost';
            $dbname = $connectionConfig['database'] ?? '';
            $user = $connectionConfig['username'] ?? '';
            $password = $connectionConfig['password'] ?? '';
            $port = $connectionConfig['port'] ?? 5432;

            if (!is_string($host) || !is_string($dbname) || !is_string($user) || !is_string($password)) {
                throw new \RuntimeException('Database connection parameters must be strings');
            }

            if (!is_int($port) && !is_string($port)) {
                throw new \RuntimeException('Database port must be int or string');
            }

            $connectionParams = [
                'host' => $host,
                'dbname' => $dbname,
                'user' => $user,
                'password' => $password,
                'port' => is_int($port) ? $port : intval($port),
                'driver' => $driver,
            ];
        }

        $this->dbConnection = DriverManager::getConnection($connectionParams, new Configuration());

        $this->tempDir = sys_get_temp_dir() . '/tswink_config_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function generateWithConfig(ExpressionStringGenerationOptions $options): string
    {
        $sources = [__DIR__ . "/Input"];
        $classesDestination = $this->tempDir . "/classes";
        $enumsDestination = $this->tempDir . "/enums";

        (new TswinkGenerator($this->dbConnection, true))->generate(
            $sources,
            $classesDestination,
            $enumsDestination,
            $options
        );

        $content = file_get_contents($classesDestination . "/TestClass.ts");
        if ($content === false) {
            throw new \RuntimeException('Failed to read generated TestClass.ts file');
        }

        return $content;
    }

    public function testClassGeneration(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: false,
            forceOptional: false
        );

        $content = $this->generateWithConfig($options);

        // Should generate class, not interface
        $this->assertStringContainsString("export default class TestClass extends BaseModel", $content);
        $this->assertStringNotContainsString("export default interface TestClass", $content);

        // Should have constructor for classes
        $this->assertStringContainsString("constructor(init?: Partial<TestClass>)", $content);

        // Should have static constants inside class
        $this->assertStringContainsString("static readonly TEST_CONST: number = 45.6;", $content);
        $this->assertStringContainsString("static readonly TEST_CONST_STRING: string = 'test';", $content);

        // Required properties (not forced optional)
        $this->assertStringContainsString("students: User[];", $content);
        $this->assertStringContainsString("tags: SetRequired<Tag, 'assignment'>[];", $content);
    }

    public function testInterfaceGeneration(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            forceOptional: true
        );

        $content = $this->generateWithConfig($options);

        // Should generate interface, not class
        $this->assertStringContainsString("export default interface TestClass extends BaseModel", $content);
        $this->assertStringNotContainsString("export default class TestClass", $content);

        // Should not have constructor for interfaces
        $this->assertStringNotContainsString("constructor(init?: Partial<TestClass>)", $content);

        // Should have separate constants object
        $this->assertStringContainsString("export const TestClassConstants = {", $content);
        $this->assertStringContainsString("TEST_CONST: 45.6,", $content);

        // Optional properties (forced optional)
        $this->assertStringContainsString("students?: User[];", $content);
        $this->assertStringContainsString("tags?: SetRequired<Tag, 'assignment'>[];", $content);
    }

    public function testRequiredPropertiesInInterface(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            forceOptional: false  // Don't force optional
        );

        $content = $this->generateWithConfig($options);

        // Should generate interface
        $this->assertStringContainsString("export default interface TestClass extends BaseModel", $content);

        // Should have required properties (not forced optional) - but relations can still be optional
        $this->assertStringContainsString("students?: User[];", $content);
        $this->assertStringContainsString("tags?: SetRequired<Tag, 'assignment'>[];", $content);

        // But non-nullable database columns should be required
        $this->assertStringContainsString("id: number;", $content);
        $this->assertStringContainsString("name: string;", $content);

        // Nullable database columns should still be optional
        $this->assertStringContainsString("nullable_student_count?: number;", $content);
        $this->assertStringContainsString("nullable_student2_count?: number;", $content);
    }

    public function testSingleQuotesImports(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            useSingleQuotes: true
        );

        $content = $this->generateWithConfig($options);

        // Should use single quotes for imports
        $this->assertStringContainsString("import type { SetRequired } from '@universite-laval/script-components'", $content);
        $this->assertStringContainsString("import type BaseModel from './BaseModel'", $content);
        $this->assertStringContainsString("import type User from './User'", $content);
    }

    public function testDoubleQuotesImports(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            useSingleQuotes: false
        );

        $content = $this->generateWithConfig($options);

        // Should use double quotes for imports
        $this->assertStringContainsString('import type { SetRequired } from "@universite-laval/script-components"', $content);
        $this->assertStringContainsString('import type BaseModel from "./BaseModel"', $content);
        $this->assertStringContainsString('import type User from "./User"', $content);
    }

    public function testSemicolonUsage(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            useSemicolon: true
        );

        $content = $this->generateWithConfig($options);

        // Should have semicolons
        $this->assertStringContainsString("id: number;", $content);
        $this->assertStringContainsString("name: string;", $content);
    }

    public function testNoSemicolonUsage(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            useSemicolon: false
        );

        $content = $this->generateWithConfig($options);

        // Should not have semicolons
        $this->assertStringContainsString("id: number", $content);
        $this->assertStringContainsString("name: string", $content);
        $this->assertStringNotContainsString("id: number;", $content);
        $this->assertStringNotContainsString("name: string;", $content);
    }

    public function testIndentationSpaces(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            useSpaces: true,
            indentSpaces: 2
        );

        $content = $this->generateWithConfig($options);

        // Should use 2 spaces for indentation
        $lines = explode("\n", $content);
        $propertyLine = null;
        foreach ($lines as $line) {
            if (strpos($line, "id: number") !== false) {
                $propertyLine = $line;
                break;
            }
        }

        $this->assertNotNull($propertyLine, "Could not find property line");
        $this->assertStringStartsWith("  id: number", $propertyLine, "Should use 2 spaces for indentation");
    }

    public function testConfigFileIntegration(): void
    {
        // Test that the default config file settings work
        $options = $this->createExpressionOptionsFromConfig();

        $content = $this->generateWithConfig($options);

        // Based on the current tswink.php config:
        // ts_use_interface_instead_of_class = true
        // ts_force_properties_optional = true

        $this->assertStringContainsString("export default interface TestClass", $content);
        $this->assertStringContainsString("students?: User[];", $content);
        $this->assertStringContainsString("export const TestClassConstants = {", $content);
    }

    public function testSeparateNewModelSetRequiredImport(): void
    {
        // Test that SetRequired import doesn't get "New" prefix in new model files
        $options = $this->createExpressionOptionsForTest(
            useInterface: true,
            createSeparateClass: true
        );

        $sources = [__DIR__ . "/Input"];
        $classesDestination = $this->tempDir . "/classes";
        $enumsDestination = $this->tempDir . "/enums";

        (new TswinkGenerator($this->dbConnection, true))->generate(
            $sources,
            $classesDestination,
            $enumsDestination,
            $options
        );

        // Check that NewTestClass.ts was created
        $newClassFile = $classesDestination . "/NewTestClass.ts";
        $this->assertFileExists($newClassFile, "NewTestClass.ts should be generated");

        $content = file_get_contents($newClassFile);
        $this->assertNotFalse($content, "Should be able to read NewTestClass.ts");

        // SetRequired import should NOT have "New" prefix (it's an external import)
        $this->assertStringContainsString(
            'import type { SetRequired } from "@universite-laval/script-components"',
            $content,
            "SetRequired import should not have 'New' prefix - it's external"
        );

        // But internal model imports SHOULD have "New" prefix
        $this->assertStringContainsString(
            'import type NewUser from "./NewUser"',
            $content,
            "Internal model imports should have 'New' prefix"
        );

        // Pivot imports should NOT have "New" prefix (they are not models)
        $this->assertStringContainsString(
            'import type TestClassTagPivot from "./TestClassTagPivot"',
            $content,
            "Pivot imports should not have 'New' prefix - they are always nullable unlike models"
        );

        // Make sure we're not accidentally adding "New" to pivot imports
        $this->assertStringNotContainsString(
            'import type NewTestClassTagPivot from "./NewTestClassTagPivot"',
            $content,
            "Pivot imports should never have 'New' prefix"
        );
    }

    public function testDateTypesInClasses(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: false
        );

        $content = $this->generateWithConfig($options);

        // Classes should use Date type for date fields
        $this->assertStringContainsString("created_at?: Date;", $content);
        $this->assertStringContainsString("updated_at?: Date;", $content);
    }

    public function testDateTypesInInterfaces(): void
    {
        $options = $this->createExpressionOptionsForTest(
            useInterface: true
        );

        $content = $this->generateWithConfig($options);

        // Interfaces should use string type for date fields
        $this->assertStringContainsString("created_at?: string;", $content);
        $this->assertStringContainsString("updated_at?: string;", $content);

        // Should NOT contain Date type for date fields in interfaces
        $this->assertStringNotContainsString("created_at?: Date;", $content);
        $this->assertStringNotContainsString("updated_at?: Date;", $content);
    }
}
