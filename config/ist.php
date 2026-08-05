<?php

return [
    'development_dataset_path' => database_path('data/ist-development'),
    'development_database' => env('IST_DEVELOPMENT_DATABASE'),
    'development_version_min' => 900000000,
    'development_version_max' => 909999999,
    'development_marker' => '[DEV:ist-development]',
    'development_media_disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Final question-bank pipeline
    |--------------------------------------------------------------------------
    |
    | Final-bank imports are deliberately separate from development imports.
    | An actual write also requires the command's --allow-database value to
    | match both the configured and active database names.
    |
    */
    'final_instrument_identifier' => 'tes-kemampuan-kognitif-adaptasi-104',
    'final_product_name' => 'Tes Kemampuan Kognitif Adaptasi',
    'final_version_min' => 100000000,
    'final_version_max' => 899999999,
    'final_media_disk' => env('IST_FINAL_MEDIA_DISK', 'public'),
    'final_media_max_bytes' => 2 * 1024 * 1024,
    'final_media_max_dimension' => 4096,
    'final_database_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('IST_FINAL_DATABASE_ALLOWLIST', '')),
    ))),
];
