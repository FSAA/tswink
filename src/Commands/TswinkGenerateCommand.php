<?php

namespace TsWink\Commands;

use Illuminate\Console\Command;
use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TswinkGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tswink:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate typescript classes from Laravel models.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connectionParams = [];
        /** @var array{driver: string, database: string, username: string} $connectionParams */
        $connectionParams = DB::getConfig();
        $connectionParams['driver'] = "pdo_" . $connectionParams['driver'];
        $connectionParams['dbname'] = $connectionParams['database'];
        $connectionParams['user'] = $connectionParams['username'];
        /** @var array{driver: key-of<DriverManager::DRIVER_MAP>, database: string, username: string} $connectionParams */
        $connection = DriverManager::getConnection($connectionParams, new Configuration());

        $sources = Config::get('tswink.php_classes_paths');
        if (!$sources || !is_array($sources)) {
            throw new Exception("The 'tswink.php_classes_paths' configuration must be an array.");
        }
        $classesDestination = Config::get('tswink.ts_classes_destination');
        if (!$classesDestination || !is_string($classesDestination)) {
            throw new Exception("The 'tswink.ts_classes_destination' configuration must be a string.");
        }
        $enumsDestination = Config::get('tswink.ts_enums_destination');
        if (!$enumsDestination || !is_string($enumsDestination)) {
            throw new Exception("The 'tswink.ts_enums_destination' configuration must be a string.");
        }
        $codeGenerationOptions = new ExpressionStringGenerationOptions();
        $indentationNumberOfSpaces = Config::get('tswink.ts_indentation_number_of_spaces');
        if ($indentationNumberOfSpaces) {
            if (!is_int($indentationNumberOfSpaces)) {
                throw new Exception("The 'tswink.ts_indentation_number_of_spaces' configuration must be an integer.");
            }
            $codeGenerationOptions->indentNumberOfSpaces = $indentationNumberOfSpaces;
        }
        $spacesInsteadOfTabs = Config::get('tswink.ts_spaces_instead_of_tabs');
        if ($spacesInsteadOfTabs) {
            if (!is_bool($spacesInsteadOfTabs)) {
                throw new Exception("The 'tswink.ts_spaces_instead_of_tabs' configuration must be a boolean.");
            }
            $codeGenerationOptions->indentUseSpaces = $spacesInsteadOfTabs;
        }
        $useSingleQuotesForImports = Config::get('tswink.ts_use_single_quotes_for_imports');
        if ($useSingleQuotesForImports) {
            if (!is_bool($useSingleQuotesForImports)) {
                throw new Exception("The 'tswink.ts_use_single_quotes_for_imports' configuration must be a boolean.");
            }
            $codeGenerationOptions->useSingleQuotesForImports = $useSingleQuotesForImports;
        }
        $useInterfaceInsteadOfClass = Config::get('tswink.ts_use_interface_instead_of_class');
        if (!is_bool($useInterfaceInsteadOfClass)) {
            throw new Exception("The 'tswink.ts_use_interface_instead_of_class' configuration must be a boolean.");
        }
        $codeGenerationOptions->useInterfaceInsteadOfClass = $useInterfaceInsteadOfClass;
        $useSemicolon = Config::get('tswink.ts_use_semicolon');
        if (!is_bool($useSemicolon)) {
            throw new Exception("The 'tswink.ts_use_semicolon' configuration must be a boolean.");
        }
        $codeGenerationOptions->useSemicolon = $useSemicolon;
        $forcePropertiesOptional = Config::get('tswink.ts_force_properties_optional');
        if (!is_bool($forcePropertiesOptional)) {
            throw new Exception("The 'tswink.ts_force_properties_optional' configuration must be a boolean.");
        }
        $codeGenerationOptions->forcePropertiesOptional = $forcePropertiesOptional;

        (new TswinkGenerator($connection))->generate($sources, $classesDestination, $enumsDestination, $codeGenerationOptions);

        $this->info("TypeScript classes have been generated.");
    }
}
