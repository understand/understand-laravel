<?php namespace Understand\UnderstandLaravel5;

use Illuminate\Foundation\Application;
use Understand\UnderstandLaravel5\Facades\UnderstandFieldProvider;

class JsProvider
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the Url of the JS bundle
     *
     * @return string
     */
    public function getJsBundleUrl()
    {
        return $this->app['config']->get('understand-laravel.cdn', 'https://cdn.understand.io/understand-js/beta/bundle.min.js');
    }

    /**
     * Get the JS configuration
     *
     * @return array
     */
    public function getJsConfig()
    {
        return [
            'env' => UnderstandFieldProvider::getEnvironment(),
            'token' => $this->app['config']->get('understand-laravel.token'),
            'context' => [
                'session_id' => UnderstandFieldProvider::getSessionId(),
                'request_id' => UnderstandFieldProvider::getProcessIdentifier(),
                'user_id' => UnderstandFieldProvider::getUserId(),
                'client_ip' => UnderstandFieldProvider::getClientIp()
            ]
        ];
    }
}