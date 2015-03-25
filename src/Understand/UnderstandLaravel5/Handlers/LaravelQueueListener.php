<?php namespace Understand\UnderstandLaravel5\Handlers;

class LaravelQueueListener
{

    /**
     * Listen queue call
     *
     * @param object $job
     * @param array $data
     */
    public function listen($job, $data)
    {
        $requestData = $data['requestData'];
        $inputToken = \Config::get('understand-laravel.token');
        $apiUrl = \Config::get('understand-laravel.url', 'https://api.understand.io');
        $silent = \Config::get('understand-laravel.silent');
        $sslBundlePath = \Config::get('understand-laravel.ssl_ca_bundle');

        $syncHandler = new SyncHandler($inputToken, $apiUrl, $silent, $sslBundlePath);
        $syncHandler->handle($requestData);

        $job->delete();
    }

}
