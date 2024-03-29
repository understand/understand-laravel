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
     * Collect SQL queries
     */
    'sql_enabled' => env('UNDERSTAND_SQL', true),

    /**
     * Send SQL values/bindings together with SQL queries
     */
    'sql_bindings' => env('UNDERSTAND_SQL_BINDINGS', false),

    /**
     * Collect a request query string data
     */
    'query_string_enabled' => env('UNDERSTAND_QUERY_STRING', true),

    /**
     * Collect a request form or JSON data
     */
    'post_data_enabled' => env('UNDERSTAND_POST_DATA', true),

    /**
     * SSL CA Bundle location
     */
    'ssl_ca_bundle' => null,

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

    /**
     * Log filter.
     *
     * The configuration value (filter) must be a callable type:
     * - https://www.php.net/manual/en/function.is-callable.php
     * or a callable dependency from the service container:
     * - https://laravel.com/docs/9.x/container#the-make-method
     *
     * The suggested way would be to create an invokable class since it's hard to serialise anonymous functions (Laravel config cache):
     * - https://www.php.net/manual/en/language.oop5.magic.php#object.invoke
     *
     * The log (callable) filter interface is as follows: `$callable($level, $message, $context)`.
     *
     * The result of the filter must be a boolean value:
     * - TRUE, the log should be ignored and NOT delivered to Understand.io
     * - FALSE, the log should be delivered to Understand.io
     *
     * The `ignored_logs` config value has higher precedence than `log_filter`.
     */
    'log_filter' => null,

    /**
     * Field names which values should not be sent to Understand.io
     * It applies to POST and GET request parameters
     */
    'hidden_fields' => explode(',', env('UNDERSTAND_HIDDEN_REQUEST_FIELDS', 'password,password_confirmation,access_token,secret_key,token,access_key')),
];
