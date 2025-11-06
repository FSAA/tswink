<?php

namespace TsWinkTests\Units;

use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Exception;
use Illuminate\Support\Facades\DB;

class TswinkGeneratorEnumServiceErrorHandlingTest extends TestCase
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

    public function testEnumMethodReturningNonArray(): void
    {
        // Create a test enum that returns a non-array value
        $enumContent = '<?php
namespace TsWinkTests\Units\Input;
use TsWink\Attributes\ExportToTypescript;

enum BadEnum: int {
    case CASE1 = 1;
    case CASE2 = 2;

    /**
     * @return string
     */
    #[ExportToTypescript]
    public static function badMethod(): string {
        return "not an array";
    }
}';

        $tempFile = __DIR__ . '/Input/BadEnum.php';
        file_put_contents($tempFile, $enumContent);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('must return an array, got string');

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
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testEnumMethodReturningNonEnumValues(): void
    {
        // Create a test enum that returns an array with non-enum values
        $enumContent = '<?php
namespace TsWinkTests\Units\Input;
use TsWink\Attributes\ExportToTypescript;

enum BadEnum2: int {
    case CASE1 = 1;
    case CASE2 = 2;

    /**
     * @return array<mixed>
     */
    #[ExportToTypescript]
    public static function badMethod(): array {
        return ["string", 123, self::CASE1];
    }
}';

        $tempFile = __DIR__ . '/Input/BadEnum2.php';
        file_put_contents($tempFile, $enumContent);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('must return an array of enum cases only');
            $this->expectExceptionMessage('Found string at index 0');

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
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testEnumMethodReturningWrongEnumCases(): void
    {
        // Create two test enums where one returns cases from the other
        $enum1Content = '<?php
namespace TsWinkTests\Units\Input;

enum OtherEnum: int {
    case OTHER1 = 10;
    case OTHER2 = 20;
}';

        $enum2Content = '<?php
namespace TsWinkTests\Units\Input;
use TsWink\Attributes\ExportToTypescript;

enum BadEnum3: int {
    case CASE1 = 1;
    case CASE2 = 2;

    /**
     * @return array<\UnitEnum>
     */
    #[ExportToTypescript]
    public static function badMethod(): array {
        return [OtherEnum::OTHER1, self::CASE1]; // Mixed enums!
    }
}';

        $tempFile1 = __DIR__ . '/Input/OtherEnum.php';
        $tempFile2 = __DIR__ . '/Input/BadEnum3.php';
        file_put_contents($tempFile1, $enum1Content);
        file_put_contents($tempFile2, $enum2Content);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('returned enum case from wrong enum class');
            $this->expectExceptionMessage('Found TsWinkTests\Units\Input\OtherEnum::OTHER1');
            $this->expectExceptionMessage('expected cases from TsWinkTests\Units\Input\BadEnum3');

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
        } finally {
            if (file_exists($tempFile1)) {
                unlink($tempFile1);
            }
            if (file_exists($tempFile2)) {
                unlink($tempFile2);
            }
        }
    }

    public function testEnumMethodThrowingException(): void
    {
        // Create a test enum method that throws an exception
        $enumContent = '<?php
namespace TsWinkTests\Units\Input;
use TsWink\Attributes\ExportToTypescript;

enum BadEnum4: int {
    case CASE1 = 1;
    case CASE2 = 2;

    /**
     * @return array<self>
     */
    #[ExportToTypescript]
    public static function badMethod(): array {
        throw new \Exception("Method execution failed");
    }
}';

        $tempFile = __DIR__ . '/Input/BadEnum4.php';
        file_put_contents($tempFile, $enumContent);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to execute method TsWinkTests\Units\Input\BadEnum4::badMethod()');
            $this->expectExceptionMessage('Method execution failed');

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
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
