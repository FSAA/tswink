<?php

return [
    // Destination of typescript interfaces
    'ts_classes_destination' => 'resources/assets/src/models',
    // Set to true to make all the interfaces' properties nullable (if not of type any)
    'ts_all_properties_nullable' => false,
    // Set to true to make all the nullable column also nullable in the interfaces.
    'ts_test_for_optional_properties' => false,
    'ts_spaces_instead_of_tabs' => false,
    // If using spaces instead of tabs, how many spaces?
    'ts_indentation_number_of_spaces' => 4,

    // Array of paths to the models (so you could include vendors models too). Paths need to be relative to the laravel install root folder
    'models_paths' => []
];
