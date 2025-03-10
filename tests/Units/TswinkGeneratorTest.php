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

        $this->assertEquals(true, true);
    }
}
