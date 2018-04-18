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

    /**
     * SSL CA Bundle location
     */
    'ssl_ca_bundle' => base_path('vendor/understand/understand-laravel5/src/ca_bundle.crt'),

    /**
     * The log types that should not be sent to Understand.io.
     *
     * By default, send everything.
     */
    'ignored_logs' => [
        //'debug',
        //'info',
        //'notice',
        //'warning',
        //'error',
        //'critical',
        //'alert',
        //'emergency',
    ],
];
