<?php

return [
    // Destination of typescript models
    'ts_classes_destination' => 'resources/js/models',
    // Destination of typescript enums
    'ts_enums_destination' => 'resources/js/enums',
    // Set to true to use spaces instead of tabs.
    'ts_spaces_instead_of_tabs' => false,
    // If using spaces instead of tabs, how many spaces?
    'ts_indentation_number_of_spaces' => 4,
    // Set to true to use single quotes for imports
    'ts_use_single_quotes_for_imports' => false,
    // Set to true to use interfaces instead of classes.
    'ts_use_interface_instead_of_class' => false,
    // Set to true to use semicolons at the end of the lines.
    'ts_use_semicolon' => true,
    // Set to true to make all model properties optional even if not nullable in the database
    'ts_force_properties_optional' => true,
    // Set to true to create a separate class for empty models (new instance records that haven't been saved to the database yet, all properties will be optional)
    'ts_create_separate_class_for_new_models' => false,

    // Array of paths to the classes (so you could include vendors models too). Paths need to be relative to the laravel install root folder
    'php_classes_paths' => []
];
