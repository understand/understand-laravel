<?php namespace Understand\UnderstandLaravel5\Handlers;

class AsyncHandler extends BaseHandler
{

    /**
     * @param string $escapedData
     * @return string|void
     */
    protected function send($escapedData)
    {
        $parts = [
            'curl',
            '-X POST',
            '--cacert',
            $this->sslBundlePath,
            '-d',
            $escapedData,
            $this->getEndpoint(),
            '> /dev/null 2>&1 &'
        ];

        $cmd = implode(' ', $parts);

        exec($cmd);
    }

    /**
     * @param $requestData
     * @return string|void
     */
    protected function escapeshellarg($requestData)
    {
        // the `escapeshellarg` function throws a fatal error in Unix if the size exceeds 2097152 bytes (~2mb)
        if (strlen($requestData) >= 2000000)
        {
            return;
        }

        return escapeshellarg($requestData);
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
        $escapedData = $this->escapeshellarg($json);

        if ($escapedData)
        {
            return $this->send($escapedData);
        }
    }
}
