<?php

namespace TsWink\Commands;

use Illuminate\Console\Command;
use TsWink\Classes\TswinkGenerator;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;

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
        $connectionParams = app('db')->getConfig();
        $connectionParams['driver'] = "pdo_" . $connectionParams['driver'];
        $connectionParams['dbname'] = $connectionParams['database'];
        $connectionParams['user'] = $connectionParams['username'];
        $connection = DriverManager::getConnection($connectionParams, new Configuration);

        $sources = config('tswink.php_classes_paths');
        $classes_destination = config('tswink.ts_classes_destination');
        $enums_destination = config('tswink.ts_enums_destination');
        $code_generation_options = new ExpressionStringGenerationOptions();
        $code_generation_options->indent_number_of_spaces = config('tswink.ts_indentation_number_of_spaces');
        $code_generation_options->indent_use_spaces = config('tswink.ts_spaces_instead_of_tabs');

        (new TswinkGenerator($connection))->generate($sources, $classes_destination, $enums_destination, $code_generation_options);
        
        $this->info("TypeScript classes have been generated.");
    }
}
