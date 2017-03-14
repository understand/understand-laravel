<?php namespace Understand\UnderstandLaravel5;

use Understand\UnderstandLaravel5\FieldProvider;
use Understand\UnderstandLaravel5\Handlers\BaseHandler;

class Logger
{

    /**
     * Field provider
     *
     * @var Understand\UnderstandLaravel\FieldProvider
     */
    protected $fieldProvider;

    /**
     * Transport layer
     *
     * @var Understand\UnderstandLaravel\Handlers\BaseHandler
     */
    protected $handler;

    /**
     * Specifies whether logger should throw an exception of issues detected
     *
     * @var bool
     */
    protected $silent = true;

    /**
     * @param \Understand\UnderstandLaravel\FieldProvider $fieldProvider
     * @param \Understand\UnderstandLaravel\Handlers\BaseHandler $handler
     * @param bool $silent
     */
    public function __construct(FieldProvider $fieldProvider, BaseHandler $handler, $silent = true)
    {
        $this->setFieldProvider($fieldProvider);
        $this->setHandler($handler);
        $this->silent = $silent;
    }

    /**
     * Resolve additonal fields and send event
     *
     * @param mixed $log
     * @param array $additional
     * @return array
     */
    public function log($log, array $additional = [])
    {
        $event = $this->prepare($log, $additional);

        return $this->send($event);
    }

    /**
     * Send multiple events
     *
     * @param array $data
     * @return array
     */
    public function bulkLog(array $events, array $additional = [])
    {
        foreach ($events as $key => $event)
        {
            $events[$key] = $this->prepare($event, $additional);
        }

        return $this->send($events);
    }

    /**
     * Format data
     *
     * @param mixed $log
     * @param array $additional
     * @return type
     */
    protected function prepare($log, array $additional = [])
    {
        // integer, float, string or boolean as message
        if (is_scalar($log))
        {
            $log = ['message' => $log];
        }
        
        if (isset($log['message']))
        {
            $log['message'] = $this->formatMessage($log['message']);
        }

        // resolve additonal properties from field providers
        $data = $this->fieldProvider->resolveValues($additional);

        $event = $data + $log;

        if (!isset($event['timestamp']))
        {
            $event['timestamp'] = round(microtime(true) * 1000);
        }

        return $event;
    }
    
    /**
     * Format message field
     * 
     * @param string $message
     * @return string
     */
    protected function formatMessage($message)
    {
        if ( ! is_bool($message))
        {
            return (string)$message;
        }
        
        // cast boolean values to "1" or "0" strings
        if ($message)
        {
            return '1';
        }
        
        return '0';
    }

    /**
     * Set handler
     *
     * @param BaseHandler $handler
     */
    public function setHandler(BaseHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Set field provider
     *
     * @param \Understand\UnderstandLaravel\FieldProvider $fieldProvider
     */
    public function setFieldProvider(FieldProvider $fieldProvider)
    {
        $this->fieldProvider = $fieldProvider;
    }

    /**
     * Send data to storage
     *
     * @param array $requestData
     * @return mixed
     */
    protected function send(array $event)
    {
        try
        {
            return $this->handler->handle($event);
        }
        catch (\Exception $ex)
        {
            if (! $this->silent)
            {
                throw new $ex;
            }

            return false;
        }
    }

}
