<?php namespace Understand\UnderstandLaravel5;

use Understand\UnderstandLaravel5\FieldProvider;
use Understand\UnderstandLaravel5\Handlers\BaseHandler;

class Logger
{

    /**
     * Version Number
     */
    const VERSION = 2.1;

    /**
     * Field provider
     *
     * @var FieldProvider
     */
    protected $fieldProvider;

    /**
     * Transport layer
     *
     * @var BaseHandler
     */
    protected $handler;

    /**
     * @param FieldProvider $fieldProvider
     * @param BaseHandler $handler
     * @param bool $silent
     */
    public function __construct(FieldProvider $fieldProvider, BaseHandler $handler)
    {
        $this->setFieldProvider($fieldProvider);
        $this->setHandler($handler);
    }

    /**
     * @param $log
     * @param array $additional
     * @param array $customFields
     * @return mixed
     */
    public function log($log, array $additional = [], array $customFields = [])
    {
        $event = $this->prepare($log, $additional, $customFields);

        return $this->send($event);
    }

    /**
     * @param $log
     * @param array $additional
     * @param array $customFields
     * @return array
     */
    protected function prepare($log, array $additional = [], array $customFields = [])
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

        // resolve additional properties from field providers
        $data = $this->resolveData($log, $additional, $customFields);

        $event = $data + $log;

        if (!isset($event['timestamp']))
        {
            $event['timestamp'] = round(microtime(true) * 1000);
        }

        return $event;
    }

    /**
     * @param $log
     * @param array $additional
     * @param array $customFields
     * @return array
     */
    protected function resolveData($log, array $additional = [], array $customFields = [])
    {
        $data = $this->fieldProvider->resolveValues($additional, $log);

        if ($customFields)
        {
            $data['custom'] = $this->fieldProvider->resolveValues($customFields, $log);
        }

        return $data;
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
     * @param FieldProvider $fieldProvider
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
        catch (\Throwable $e)
        {
            return false;
        }
        catch (\Exception $ex)
        {
            return false;
        }
    }
}
