<?php namespace Understand\UnderstandLaravel5\Handlers;

class CallbackHandler extends BaseHandler
{

    /**
     * Callback
     *
     * @var callable
     */
    protected $callback;

    /**
     * Set callback
     *
     * @param type $callback
     * @return void
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Call user defined callback with request data
     *
     * @param mixed $requestData
     * @return mixed
     */
    protected function send($requestData)
    {
        return call_user_func_array($this->callback, [$requestData]);
    }
}
