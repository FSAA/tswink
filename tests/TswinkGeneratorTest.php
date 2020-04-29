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
        $connectionParams['host'] = //Database host here;
        $connectionParams['dbname'] = //Database name here;
        $connectionParams['user'] = //Username here;
        $connectionParams['password'] = //Password here;
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