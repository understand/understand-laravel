<?php namespace Understand\UnderstandLaravel5;

use Illuminate\Config\Repository;

class EventLogger
{

    /**
     * Log writer
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * @param Logger $logger
     * @param Repository $config
     */
    public function __construct(
        Logger $logger,
        Repository $config
    )
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param $level
     * @param $message
     * @param $context
     */
    public function logEvent($level, $message, $context)
    {
        // integer, float, string or boolean as message
        if (is_scalar($message))
        {
            $log = [
                'message' => $message
            ];
        }
        else
        {
            $log = (array)$message;
        }

        $log['tags'] = ['laravel_log'];
        $log['level'] = $level;

        if ($context)
        {
            $log['context'] = (array)$context;
        }

        $additionalFields = $this->getMetaFields();
        $customFields = $this->config->get('understand-laravel.events.meta', []);

        $this->logger->log($log, $additionalFields, $customFields);
    }

    /**
     * @return array
     */
    protected function getMetaFields()
    {
        return [
            'session_id' => 'UnderstandFieldProvider::getSessionId',
            'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
            'user_id' => 'UnderstandFieldProvider::getUserId',
            'env' => 'UnderstandFieldProvider::getEnvironment',
            'url' => 'UnderstandFieldProvider::getUrl',
            'method' => 'UnderstandFieldProvider::getRequestMethod',
            'client_ip' => 'UnderstandFieldProvider::getClientIp',
            'server_ip' => 'UnderstandFieldProvider::getServerIp',
        ];
    }
}