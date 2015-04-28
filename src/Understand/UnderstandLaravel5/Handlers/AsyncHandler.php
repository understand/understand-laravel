<?php namespace Understand\UnderstandLaravel5\Handlers;

class AsyncHandler extends BaseHandler
{

    /**
     * Send data to storage
     *
     * @param string $requestData
     * @return void
     */
    protected function send($requestData)
    {
        $parts = [
            'curl',
            '-X POST',
            '--cacert',
            $this->sslBundlePath,
            '-d',
            escapeshellarg($requestData),
            $this->getEndpoint(),
            '> /dev/null 2>&1 &'
        ];

        $cmd = implode(' ', $parts);

        exec($cmd);
    }

    /**
     * Serialize data and send to storage
     *
     * @param array $requestData
     * @return void
     */
    public function handle(array $requestData)
    {
        $json = json_encode($requestData);

        $this->send($json);
    }
}
