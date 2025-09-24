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

        (new TswinkGenerator($this->dbConnection))->generate($sources, $classesDestination, $enumsDestination, new ExpressionStringGenerationOptions());

        // Test that basic class generation works
        $this->assertFileExists($classesDestination . "/TestClass.ts");
        $this->assertFileExists($classesDestination . "/Tag.ts");

        // Test that pivot interface was generated
        $this->assertFileExists($classesDestination . "/TestClassTagPivot.ts");

        // Test pivot interface content
        $pivotContent = file_get_contents($classesDestination . "/TestClassTagPivot.ts");
        $this->assertNotFalse($pivotContent, "Failed to read pivot interface file");
        $this->assertStringContainsString("export interface TestClassTagPivot", $pivotContent);
        $this->assertStringContainsString("priority: number", $pivotContent);
        $this->assertStringContainsString("assigned_at?: Date", $pivotContent);

        // Test that TestClass has the correct relation with SetRequired
        $testClassContent = file_get_contents($classesDestination . "/TestClass.ts");
        $this->assertNotFalse($testClassContent, "Failed to read TestClass file");
        $this->assertStringContainsString("tags: SetRequired<Tag, 'assignment'>[]", $testClassContent);

        // Test that Tag has the pivot property and bidirectional relation
        $tagContent = file_get_contents($classesDestination . "/Tag.ts");
        $this->assertNotFalse($tagContent, "Failed to read Tag file");
        $this->assertStringContainsString("assignment?: TestClassTagPivot", $tagContent);
        $this->assertStringContainsString("import TestClassTagPivot from \"./TestClassTagPivot\"", $tagContent);
        $this->assertStringContainsString("test_classes: SetRequired<TestClass, 'assignment'>[]", $tagContent);

        // Test that TestClass also has the pivot property (bidirectional)
        $this->assertStringContainsString("assignment?: TestClassTagPivot", $testClassContent);
    }
}
