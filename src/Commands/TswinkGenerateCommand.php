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
        if (!$sources || !is_array($sources) || !array_reduce($sources, fn ($carry, $item) => $carry && is_string($item), true)) {
            throw new Exception("The 'tswink.php_classes_paths' configuration must be an array of strings.");
        }
        /** @var string[] $sources */
        $classesDestination = Config::get('tswink.ts_classes_destination');
        if (!$classesDestination || !is_string($classesDestination)) {
            throw new Exception("The 'tswink.ts_classes_destination' configuration must be a string.");
        }
        $enumsDestination = Config::get('tswink.ts_enums_destination');
        if (!$enumsDestination || !is_string($enumsDestination)) {
            throw new Exception("The 'tswink.ts_enums_destination' configuration must be a string.");
        }
        $codeGenerationOptions = new ExpressionStringGenerationOptions();
        $codeGenerationOptions->indentNumberOfSpaces = $this->getOptionalIntegerConfig('tswink.ts_indentation_number_of_spaces', $codeGenerationOptions->indentNumberOfSpaces);
        $codeGenerationOptions->indentUseSpaces = $this->getOptionalBooleanConfig('tswink.ts_spaces_instead_of_tabs', $codeGenerationOptions->indentUseSpaces);
        $codeGenerationOptions->useSingleQuotesForImports = $this->getOptionalBooleanConfig('tswink.ts_use_single_quotes_for_imports', $codeGenerationOptions->useSingleQuotesForImports);
        $codeGenerationOptions->useInterfaceInsteadOfClass = $this->getOptionalBooleanConfig('tswink.ts_use_interface_instead_of_class', $codeGenerationOptions->useInterfaceInsteadOfClass);
        $codeGenerationOptions->useSemicolon = $this->getOptionalBooleanConfig('tswink.ts_use_semicolon', $codeGenerationOptions->useSemicolon);
        $codeGenerationOptions->forcePropertiesOptional = $this->getOptionalBooleanConfig('tswink.ts_force_properties_optional', $codeGenerationOptions->forcePropertiesOptional);
        $codeGenerationOptions->createSeparateClassForNewModels = $this->getOptionalBooleanConfig('tswink.ts_create_separate_class_for_new_models', $codeGenerationOptions->createSeparateClassForNewModels);

        (new TswinkGenerator($connection))->generate($sources, $classesDestination, $enumsDestination, $codeGenerationOptions);

        $this->info("TypeScript classes have been generated.");
    }

    private function getOptionalIntegerConfig(string $key, int $defaultValue = 0): int
    {
        $value = Config::get($key);
        if ($value === null) {
            return $defaultValue;
        }
        if (!is_int($value)) {
            throw new Exception("The '$key' configuration must be an integer.");
        }
        return $value;
    }

    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function getOptionalBooleanConfig(string $key, bool $defaultValue = false): bool
    {
        $value = Config::get($key);
        if ($value === null) {
            return $defaultValue;
        }
        if (!is_bool($value)) {
            throw new Exception("The '$key' configuration must be a boolean.");
        }
        return $value;
    }
}
