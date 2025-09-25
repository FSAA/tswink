<?php

namespace TsWinkTests\Units;

use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Exception;
use Illuminate\Support\Facades\DB;

class TswinkGeneratorComprehensiveTest extends TestCase
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

    // ========================================
    // SNAPSHOT TESTS - Full Integration
    // ========================================

    public function testGeneratedTestClassSnapshot(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");
        $this->assertSnapshot($testClassContent, 'TestClass.ts');
    }

    public function testGeneratedTagSnapshot(): void
    {
        $this->generateFiles();
        $tagContent = $this->getGeneratedFileContent("/Tag.ts");
        $this->assertSnapshot($tagContent, 'Tag.ts');
    }

    public function testGeneratedPivotInterfaceSnapshot(): void
    {
        $this->generateFiles();
        $pivotContent = $this->getGeneratedFileContent("/TestClassTagPivot.ts");
        $this->assertSnapshot($pivotContent, 'TestClassTagPivot.ts');
    }

    // ========================================
    // TARGETED UNIT TESTS - Critical Logic
    // ========================================

    public function testBasicFileGeneration(): void
    {
        $this->generateFiles();

        // Test that all expected files are generated
        $this->assertFileExists($this->getOutputPath("/TestClass.ts"));
        $this->assertFileExists($this->getOutputPath("/Tag.ts"));
        $this->assertFileExists($this->getOutputPath("/TestClassTagPivot.ts"));
    }

    public function testNullableTypeHandling(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test different nullable type syntaxes
        $this->assertStringContainsString("nullable_student_count?: number;", $testClassContent, "int|null should become number");
        $this->assertStringContainsString("nullable_student2_count?: number;", $testClassContent, "?int should become number");
        // Check that we don't have redundant "| undefined" in type annotations
        $this->assertStringNotContainsString("?: number | undefined", $testClassContent, "Should not have redundant | undefined with optional marker");
        $this->assertStringNotContainsString("?: string | undefined", $testClassContent, "Should not have redundant | undefined with optional marker");
    }

    public function testComplexArrayTypes(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test array type conversions
        $this->assertStringContainsString("stringArray?: Array<string>;", $testClassContent);
        $this->assertStringContainsString("deepStringArray?: Array<Array<string>>;", $testClassContent);
        $this->assertStringContainsString("anyArray?: Array<any>;", $testClassContent);

        // Test array shapes
        $this->assertStringContainsString("associativeArray?: { stringProperty: string, numberProperty: number", $testClassContent);
        $this->assertStringContainsString("complexArray?: { [key: number]: { foo: boolean } };", $testClassContent);
    }

    public function testConstantGeneration(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test constant types and values in exported constants object (interface mode)
        $this->assertStringContainsString("export const TestClassConstants = {", $testClassContent);
        $this->assertStringContainsString("TEST_CONST: 45.6,", $testClassContent);
        $this->assertStringContainsString("TEST_CONST_STRING: 'test',", $testClassContent);
        $this->assertStringContainsString("TEST_CONST_ARRAY: ['test',123,true],", $testClassContent);
        $this->assertStringContainsString("phpQualifiedClassName: 'TsWinkTests\\\\Units\\\\Input\\\\TestClass',", $testClassContent);
    }

    public function testAccessorMethodConversion(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test accessor methods become properties
        $this->assertStringContainsString("testAccessor?: string;", $testClassContent);
        $this->assertStringContainsString("tesAnyAccessor?: any;", $testClassContent);
        $this->assertStringContainsString("stringOrIntAccessor?: string | number;", $testClassContent);

        // Test tswink override works
        $this->assertStringContainsString("tswinkOverride?: string;", $testClassContent, "@tswink-property should override PHPDoc type");
    }

    public function testRelationGeneration(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test different relation types (all optional due to ts_force_properties_optional config)
        $this->assertStringContainsString("user?: User;", $testClassContent, "BelongsTo should be optional single");
        $this->assertStringContainsString("students?: User[];", $testClassContent, "HasMany should be optional array");
        $this->assertStringContainsString("tags?: SetRequired<Tag, 'assignment'>[];", $testClassContent, "BelongsToMany with pivot should use SetRequired");
    }

    public function testPivotGeneration(): void
    {
        $this->generateFiles();

        // Test pivot interface generation
        $pivotContent = $this->getGeneratedFileContent("/TestClassTagPivot.ts");
        $this->assertStringContainsString("export default interface TestClassTagPivot", $pivotContent);
        $this->assertStringContainsString("priority?: number", $pivotContent);
        $this->assertStringContainsString("assigned_at?: Date", $pivotContent);

        // Test bidirectional pivot properties
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");
        $tagContent = $this->getGeneratedFileContent("/Tag.ts");

        $this->assertStringContainsString("assignment?: SetRequired<TestClassTagPivot, 'priority'>;", $testClassContent);
        $this->assertStringContainsString("assignment?: SetRequired<TestClassTagPivot, 'priority'>;", $tagContent);
        $this->assertStringContainsString("test_classes?: SetRequired<TestClass, 'assignment'>[];", $tagContent);
    }

    public function testImportGeneration(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test required imports are generated (type imports for interfaces)
        $expectedImports = [
            'import type { SetRequired } from \'@universite-laval/script-components\'',
            'import type BaseModel from \'./BaseModel\'',
            'import type User from \'./User\'',
            'import type Tag from \'./Tag\'',
            'import type TestClassTagPivot from \'./TestClassTagPivot\''
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $testClassContent);
        }
    }

    public function testConstructorGeneration(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Interfaces don't have constructors, so we test that it's an interface instead
        $this->assertStringContainsString("export default interface TestClass extends BaseModel", $testClassContent);
        $this->assertStringNotContainsString("constructor", $testClassContent, "Interfaces should not have constructors");
    }

    public function testNonAutoGeneratedSectionsPreservation(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test that non-auto-generated sections are preserved
        $this->assertStringContainsString("// <non-auto-generated-import-declarations>", $testClassContent);
        $this->assertStringContainsString("import TestImport from \"./TestImport\"", $testClassContent);
        $this->assertStringContainsString("// </non-auto-generated-import-declarations>", $testClassContent);

        $this->assertStringContainsString("// <non-auto-generated-class-declarations>", $testClassContent);
        $this->assertStringContainsString("public testAttribute: any;", $testClassContent);
        $this->assertStringContainsString("public testFunction(): any {", $testClassContent);
        $this->assertStringContainsString("// </non-auto-generated-class-declarations>", $testClassContent);

        $this->assertStringContainsString("// <non-auto-generated-code>", $testClassContent);
        $this->assertStringContainsString("// </non-auto-generated-code>", $testClassContent);
    }

    // ========================================
    // EDGE CASE TESTS
    // ========================================

    public function testEmptyPivotHandling(): void
    {
        // Test what happens when pivot columns are empty or missing
        $this->generateFiles();
        $pivotContent = $this->getGeneratedFileContent("/TestClassTagPivot.ts");

        // Should still generate interface even if only default columns
        $this->assertStringContainsString("export default interface TestClassTagPivot", $pivotContent);
    }

    public function testTypeOverrides(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Test that @tswink-property overrides @property
        $this->assertStringContainsString("tswinkOverride?: string;", $testClassContent);
        // Should not contain the original bool type from @property
        $this->assertStringNotContainsString("tswinkOverride?: boolean;", $testClassContent);
    }

    public function testCollectionRelationTypes(): void
    {
        $this->generateFiles();
        $testClassContent = $this->getGeneratedFileContent("/TestClass.ts");

        // Collection relations are optional due to ts_force_properties_optional config
        $this->assertStringContainsString("students?: User[];", $testClassContent, "HasMany should be optional array in interface");
        $this->assertStringContainsString("tags?: SetRequired<Tag, 'assignment'>[];", $testClassContent, "BelongsToMany should be optional array in interface");
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function generateFiles(): void
    {
        $sources = ([__DIR__ . "/Input"]);
        $classesDestination = (__DIR__ . "/Output/Classes");
        $enumsDestination = (__DIR__ . "/Output/Enums");

        (new TswinkGenerator($this->dbConnection, true))->generate($sources, $classesDestination, $enumsDestination, $this->createExpressionOptionsFromConfig());
    }

    private function getOutputPath(string $relativePath): string
    {
        return __DIR__ . "/Output/Classes" . $relativePath;
    }

    private function getGeneratedFileContent(string $relativePath): string
    {
        $content = file_get_contents($this->getOutputPath($relativePath));
        $this->assertNotFalse($content, "Failed to read file: " . $relativePath);
        return $content;
    }

    private function assertSnapshot(string $actualContent, string $snapshotName): void
    {
        $snapshotPath = __DIR__ . "/snapshots/" . $snapshotName;

        // Check for UPDATE_SNAPSHOTS environment variable
        $updateSnapshots = getenv('UPDATE_SNAPSHOTS') === '1' || getenv('UPDATE_SNAPSHOTS') === 'true';

        if (!file_exists($snapshotPath) || $updateSnapshots) {
            // Create snapshot directory if it doesn't exist
            $snapshotDir = dirname($snapshotPath);
            if (!is_dir($snapshotDir)) {
                mkdir($snapshotDir, 0755, true);
            }

            // Create or update snapshot
            file_put_contents($snapshotPath, $actualContent);

            if ($updateSnapshots) {
                $this->addToAssertionCount(1); // Count as successful assertion
                return;
            }

            $this->markTestSkipped("Created new snapshot: " . $snapshotName . ". Run test again to validate.");
        }

        $expectedContent = file_get_contents($snapshotPath);
        $this->assertNotFalse($expectedContent, "Failed to read snapshot: " . $snapshotName);

        // Normalize whitespace for comparison to reduce brittleness
        $normalizedActual = $this->normalizeWhitespace($actualContent);
        $normalizedExpected = $this->normalizeWhitespace($expectedContent);

        $this->assertEquals(
            $normalizedExpected,
            $normalizedActual,
            "Generated content does not match snapshot: " . $snapshotName .
            "\nRun 'UPDATE_SNAPSHOTS=1 vendor/bin/phpunit' to update snapshots if this change is intentional."
        );
    }

    private function normalizeWhitespace(string $content): string
    {
        // Normalize line endings and trim excessive whitespace while preserving structure
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $normalizedContent = preg_replace('/[ \t]+/', ' ', $content);  // Multiple spaces/tabs to single space
        if ($normalizedContent === null) {
            $normalizedContent = $content;
        }
        $finalContent = preg_replace('/\n\s*\n\s*\n/', "\n\n", $normalizedContent);  // Multiple blank lines to double
        if ($finalContent === null) {
            $finalContent = $normalizedContent;
        }
        return trim($finalContent);
    }


}
