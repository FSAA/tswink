<?php

return [
    // Destination of typescript models
    'ts_classes_destination' => 'resources/js/models',
    // Destination of typescript enums
    'ts_enums_destination' => 'resources/js/enums',
    // Set to true to use spaces intead of tabs.
    'ts_spaces_instead_of_tabs' => false,
    // If using spaces instead of tabs, how many spaces?
    'ts_indentation_number_of_spaces' => 4,
    // Set to true to use single quotes for imports
    'ts_use_single_quotes_for_imports' => false,
    // Set to true to use interfaces instead of classes.
    'ts_use_interface_instead_of_class' => false,
    // Set to true to use semicolons at the end of the lines.
    'ts_use_semicolon' => true,

    // Array of paths to the classes (so you could include vendors models too). Paths need to be relative to the laravel install root folder
    'php_classes_paths' => []
];
