<?php

namespace TsWinkTests\Units;

use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Exception;
use Illuminate\Support\Facades\DB;

class TswinkGeneratorTest extends TestCase
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

    public function testItCanGenerateTypescriptClasses(): void
    {
        $sources = ([__DIR__ . "/Input"]);
        $classesDestination = (__DIR__ . "/Output/Classes");
        $enumsDestination = (__DIR__ . "/Output/Enums");

        (new TswinkGenerator($this->dbConnection, true))->generate($sources, $classesDestination, $enumsDestination, $this->createExpressionOptionsFromConfig());

        // Test that basic class generation works
        $this->assertFileExists($classesDestination . "/TestClass.ts");
        $this->assertFileExists($classesDestination . "/Tag.ts");

        // Test that pivot interface was generated
        $this->assertFileExists($classesDestination . "/TestClassTagPivot.ts");

        // Test pivot interface content
        $pivotContent = file_get_contents($classesDestination . "/TestClassTagPivot.ts");
        $this->assertNotFalse($pivotContent, "Failed to read pivot interface file");
        $this->assertStringContainsString("export default interface TestClassTagPivot", $pivotContent);
        $this->assertStringContainsString("priority?: number", $pivotContent);
        $this->assertStringContainsString("assigned_at?: string", $pivotContent);

        // Test that TestClass has the correct relation with SetRequired
        $testClassContent = file_get_contents($classesDestination . "/TestClass.ts");
        $this->assertNotFalse($testClassContent, "Failed to read TestClass file");
        $this->assertStringContainsString("tags?: SetRequired<Tag, 'assignment'>[]", $testClassContent);

        // Test that Tag has the pivot property and bidirectional relation
        $tagContent = file_get_contents($classesDestination . "/Tag.ts");
        $this->assertNotFalse($tagContent, "Failed to read Tag file");
        $this->assertStringContainsString("assignment?: SetRequired<TestClassTagPivot, 'priority'>", $tagContent);
        $this->assertStringContainsString("import type TestClassTagPivot from './TestClassTagPivot'", $tagContent);
        $this->assertStringContainsString("test_classes?: SetRequired<TestClass, 'assignment'>[]", $tagContent);

        // Test that TestClass also has the pivot property (bidirectional)
        $this->assertStringContainsString("assignment?: SetRequired<TestClassTagPivot, 'priority'>", $testClassContent);

        // Test constants (exported separately for interfaces)
        $this->assertStringContainsString("export const TestClassConstants = {", $testClassContent);
        $this->assertStringContainsString("TEST_CONST: 45.6,", $testClassContent);
        $this->assertStringContainsString("TEST_CONST_STRING: 'test',", $testClassContent);
        $this->assertStringContainsString("TEST_CONST_ARRAY: ['test',123,true],", $testClassContent);
        $this->assertStringContainsString("phpQualifiedClassName: 'TsWinkTests\\\\Units\\\\Input\\\\TestClass',", $testClassContent);

        // Test PHPDoc properties - arrays (interfaces don't have public keyword)
        $this->assertStringContainsString("anyArray?: Array<any>;", $testClassContent);
        $this->assertStringContainsString("stringArray?: Array<string>;", $testClassContent);
        $this->assertStringContainsString("deepStringArray?: Array<Array<string>>;", $testClassContent);

        // Test PHPDoc properties - complex array shapes (interfaces don't have public keyword)
        $this->assertStringContainsString("associativeArray?: { stringProperty: string, numberProperty: number, complexProperty: { key: string }, subArray: { [key: string]: string } };", $testClassContent);
        $this->assertStringContainsString("complexArray?: { [key: number]: { foo: boolean } };", $testClassContent);

        // Test count accessors
        $this->assertStringContainsString("student_count?: number;", $testClassContent);
        $this->assertStringContainsString("nullable_student_count?: number;", $testClassContent);
        $this->assertStringContainsString("nullable_student2_count?: number;", $testClassContent);
        $this->assertStringContainsString("test_nullable_any_count?: any;", $testClassContent);

        // Test tswink override property
        $this->assertStringContainsString("tswinkOverride?: string;", $testClassContent);

        // Test accessor methods converted to properties
        $this->assertStringContainsString("testAccessor?: string;", $testClassContent);
        $this->assertStringContainsString("tesAnyAccessor?: any;", $testClassContent);
        $this->assertStringContainsString("stringOrIntAccessor?: string | number;", $testClassContent);

        // Test database columns
        $this->assertStringContainsString("id?: number;", $testClassContent);
        $this->assertStringContainsString("name?: string;", $testClassContent);
        $this->assertStringContainsString("created_at?: string;", $testClassContent);
        $this->assertStringContainsString("updated_at?: string;", $testClassContent);
        // UUID property might not be present in interface mode
        $this->assertStringContainsString("value?: number;", $testClassContent);

        // Test relations
        $this->assertStringContainsString("user?: User;", $testClassContent);
        $this->assertStringContainsString("students?: User[];", $testClassContent);

        // Test imports (type imports for interfaces)
        $this->assertStringContainsString("import type { SetRequired } from '@universite-laval/script-components'", $testClassContent);
        $this->assertStringContainsString("import type BaseModel from './BaseModel'", $testClassContent);
        $this->assertStringContainsString("import type User from './User'", $testClassContent);
        $this->assertStringContainsString("import type Tag from './Tag'", $testClassContent);
        $this->assertStringContainsString("import type TestClassTagPivot from './TestClassTagPivot'", $testClassContent);

        // Interfaces don't have constructors - skip constructor tests

        // Test non-auto-generated sections
        $this->assertStringContainsString("// <non-auto-generated-import-declarations>", $testClassContent);
        $this->assertStringContainsString("import TestImport from \"./TestImport\"", $testClassContent);
        $this->assertStringContainsString("// </non-auto-generated-import-declarations>", $testClassContent);
        $this->assertStringContainsString("// <non-auto-generated-class-declarations>", $testClassContent);
        $this->assertStringContainsString("public testAttribute: any;", $testClassContent);
        $this->assertStringContainsString("public testFunction(): any {", $testClassContent);
        $this->assertStringContainsString("// </non-auto-generated-class-declarations>", $testClassContent);
        $this->assertStringContainsString("// <non-auto-generated-code>", $testClassContent);
        $this->assertStringContainsString("// </non-auto-generated-code>", $testClassContent);

        // Test interface structure (based on config)
        $this->assertStringContainsString("export default interface TestClass extends BaseModel {", $testClassContent);
    }
}
