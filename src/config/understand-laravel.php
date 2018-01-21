<?php

return [

    /**
     * Input key
     */
    'token' => env('UNDERSTAND_TOKEN'),

    /**
     * Enable/Disable Understand service provider
     */
    'enabled' => env('UNDERSTAND_ENABLED', true),

    /**
     * Specify which handler to use - sync, queue or async. 
     * 
     * Note that the async handler will only work in systems where 
     * the CURL command line tool is installed
     */
    'handler' => env('UNDERSTAND_HANDLER', 'sync'),

    /**
     * Project root folder
     */
    'project_root' => base_path() . DIRECTORY_SEPARATOR,

    /**
     * Collect SQL queries without bindings
     */
    'sql_enabled' => true,

    // `info`, `debug` and NOT `\Exception` or `\Throwable`
    'events' => [
        'meta' => [
            // ...
        ]
    ],

    // `notice`, `warning`, `error`, `critical`, `alert`, `emergency` and `\Exception`, `\Throwable`
    'errors' => [
        'meta' => [
            // ...
        ]
    ],

    /**
     * SSL CA Bundle location
     */
    'ssl_ca_bundle' => base_path('vendor/understand/understand-laravel5/src/ca_bundle.crt')
];
