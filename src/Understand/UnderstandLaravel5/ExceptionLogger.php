<?php namespace Understand\UnderstandLaravel5;

use Illuminate\Config\Repository;
use Exception;
use Throwable;

class ExceptionLogger
{

    /**
     * Log writer
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Exception encoder
     *
     * @var ExceptionEncoder
     */
    protected $encoder;

    /**
     * Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * @param Logger $logger
     * @param ExceptionEncoder $encoder
     * @param Repository $config
     */
    public function __construct(
        Logger $logger,
        ExceptionEncoder $encoder,
        Repository $config
    )
    {
        $this->logger = $logger;
        $this->encoder = $encoder;
        $this->config = $config;

        $this->encoder->setProjectRoot($this->config->get('understand-laravel.project_root'));
    }

    /**
     * Send PHP exception to Understand.io
     *
     * @deprecated
     * @param mixed $error
     * @return void
     */
    public function log($error)
    {
        if ( ! $this->canHandle($error))
        {
            return;
        }

        $errorArr = $this->encoder->exceptionToArray($error);

        return $this->dispatch($errorArr);
    }

    /**
     * @param string $level
     * @param mixed $message
     * @param mixed $context
     * @return void
     */
    public function logError($level, $message, $context)
    {
        if ( ! $this->canHandle($message))
        {
            return;
        }

        if ($message instanceof Exception || $message instanceof Throwable)
        {
            $log = $this->encoder->exceptionToArray($message);
        }
        // integer, float, string or boolean as message
        else if (is_scalar($message))
        {
            $log = [
                'message' => $message
            ];

            $log = $this->encoder->setCurrentStackTrace($log);
        }
        else
        {
            $log = (array)$message;
            $log = $this->encoder->setCurrentStackTrace($log);
        }

        $log['level'] = $level;

        if ($context)
        {
            $log['context'] = (array)$context;
        }

        return $this->dispatch($log);
    }

    /**
     * @param array $errorArr
     * @return array
     */
    protected function dispatch(array $errorArr)
    {
        $errorArr['tags'] = ['error_log'];

        $additionalFields = $this->getMetaFields();
        $customFields = $this->config->get('understand-laravel.errors.meta', []);

        return $this->logger->log($errorArr, $additionalFields, $customFields);
    }

    /**
     * @return array
     */
    protected function getMetaFields()
    {
        return [
            'session_id' => 'UnderstandFieldProvider::getSessionId',
            'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
            'group_id' => 'UnderstandFieldProvider::getGroupId',
            'user_id' => 'UnderstandFieldProvider::getUserId',
            'env' => 'UnderstandFieldProvider::getEnvironment',
            'url' => 'UnderstandFieldProvider::getUrl',
            'method' => 'UnderstandFieldProvider::getRequestMethod',
            'query_string_data' => 'UnderstandFieldProvider::getQueryStringArray',
            'request_body_data' => 'UnderstandFieldProvider::getPostDataArray',
            'client_ip' => 'UnderstandFieldProvider::getClientIp',
            'server_ip' => 'UnderstandFieldProvider::getServerIp',
            'user_agent' => 'UnderstandFieldProvider::getClientUserAgent',
            'laravel_version' => 'UnderstandFieldProvider::getLaravelVersion',
            'sql_queries' => 'UnderstandFieldProvider::getSqlQueries',
            'artisan_command' => 'UnderstandFieldProvider::getArtisanCommandName',
            'console' => 'UnderstandFieldProvider::getRunningInConsole',
            'logger_version' => 'UnderstandFieldProvider::getLoggerVersion',
        ];
    }

    /**
     * @param mixed $error
     * @return bool
     */
    protected function canHandle($error)
    {
        return (bool)$this->config->get('understand-laravel.enabled');
    }
}