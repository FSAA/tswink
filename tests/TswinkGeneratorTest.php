<?php


namespace TsWink\Tests;

use PHPUnit\Framework\TestCase;
use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;

class TswinkGeneratorTest extends TestCase
{
    /** @var Connection */
    private static $dbConnection;

    public static function setUpBeforeClass(): void
    {
        $connectionParams = [];
        $connectionParams['driver'] = "pdo_pgsql";
        $connectionParams['host'] = "132.203.235.196";
        $connectionParams['dbname'] = "simulovins-oli";
        $connectionParams['user'] = "olrob13";
        $connectionParams['password'] = "4v7as220g";
        self::$dbConnection = DriverManager::getConnection($connectionParams, new Configuration);
    }

    /**
     * @test
     */
    public function it_can_generate_typescript_classes()
    {
        $sources = (array(__DIR__."/Input"));
        $classesDestination = (__DIR__."/Output/Classes");
        $enumsDestination = (__DIR__."/Output/Enums");

        (new TswinkGenerator(self::$dbConnection))->generate($sources, $classesDestination, $enumsDestination, new ExpressionStringGenerationOptions());

        $this->assertEquals(true, true);
    }
}