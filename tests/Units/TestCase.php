<?php

declare(strict_types=1);

namespace TsWinkTests\Units;

use Orchestra\Testbench\TestCase as TestbenchTestCase;
use TsWink\Classes\Expressions\ExpressionStringGenerationOptions;

class TestCase extends TestbenchTestCase
{
    // use WithWorkbench;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/../database/migrations'
        );
    }

    /**
     * Create ExpressionStringGenerationOptions from tswink.php config file
     */
    protected function createExpressionOptionsFromConfig(): ExpressionStringGenerationOptions
    {
        $config = include __DIR__ . '/../../src/Config/tswink.php';

        $options = new ExpressionStringGenerationOptions();

        if (isset($config['ts_indentation_number_of_spaces']) && is_int($config['ts_indentation_number_of_spaces'])) {
            $options->indentNumberOfSpaces = $config['ts_indentation_number_of_spaces'];
        }

        if (isset($config['ts_spaces_instead_of_tabs']) && is_bool($config['ts_spaces_instead_of_tabs'])) {
            $options->indentUseSpaces = $config['ts_spaces_instead_of_tabs'];
        }

        if (isset($config['ts_use_single_quotes_for_imports']) && is_bool($config['ts_use_single_quotes_for_imports'])) {
            $options->useSingleQuotesForImports = $config['ts_use_single_quotes_for_imports'];
        }

        if (isset($config['ts_use_interface_instead_of_class']) && is_bool($config['ts_use_interface_instead_of_class'])) {
            $options->useInterfaceInsteadOfClass = $config['ts_use_interface_instead_of_class'];
        }

        if (isset($config['ts_use_semicolon']) && is_bool($config['ts_use_semicolon'])) {
            $options->useSemicolon = $config['ts_use_semicolon'];
        }

        if (isset($config['ts_force_properties_optional']) && is_bool($config['ts_force_properties_optional'])) {
            $options->forcePropertiesOptional = $config['ts_force_properties_optional'];
        }

        if (isset($config['ts_create_separate_class_for_new_models']) && is_bool($config['ts_create_separate_class_for_new_models'])) {
            $options->createSeparateClassForNewModels = $config['ts_create_separate_class_for_new_models'];
        }

        return $options;
    }

    /**
     * Create ExpressionStringGenerationOptions with specific test configuration
     */
    protected function createExpressionOptionsForTest(
        bool $useInterface = false,
        bool $forceOptional = false,
        bool $useSingleQuotes = false,
        bool $useSemicolon = true,
        bool $useSpaces = true,
        int $indentSpaces = 4,
        bool $createSeparateClass = false
    ): ExpressionStringGenerationOptions {
        $options = new ExpressionStringGenerationOptions();

        $options->useInterfaceInsteadOfClass = $useInterface;
        $options->forcePropertiesOptional = $forceOptional;
        $options->useSingleQuotesForImports = $useSingleQuotes;
        $options->useSemicolon = $useSemicolon;
        $options->indentUseSpaces = $useSpaces;
        $options->indentNumberOfSpaces = $indentSpaces;
        $options->createSeparateClassForNewModels = $createSeparateClass;

        return $options;
    }
}
