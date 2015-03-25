<?php namespace Understand\UnderstandLaravel5\Handlers;

class LaravelQueueHandler extends BaseHandler
{

    /**
     * Send data to storage
     *
     * @param mixed $requestData
     * @return type
     */
    protected function send($requestData)
    {
        try
        {
            \Queue::push('Understand\UnderstandLaravel5\Handlers\LaravelQueueListener@listen', [
                'requestData' => $requestData
            ]);
        }
        catch (\Exception $ex)
        {
            if ( ! $this->silent)
            {
                throw new \Understand\UnderstandLaravel5\Exceptions\HandlerException($ex->getMessage(), $ex->getCode(), $ex);
            }
        }
    }

    /**
     * Serialize data and send to storage
     *
     * @param array $requestData
     * @return array
     */
    public function handle(array $requestData)
    {
        return $this->send($requestData);
    }

}
