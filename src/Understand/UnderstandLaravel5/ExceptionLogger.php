<?php namespace Understand\UnderstandLaravel5;

use Understand\UnderstandLaravel5\ExceptionEncoder;
use Understand\UnderstandLaravel5\Logger;
use Illuminate\Config\Repository;

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
    }

    /**
     * Send PHP exception to Understand.io
     *
     * @param \Exception $exception
     * @return void
     */
    public function log(\Exception $exception)
    {
        if ($exception instanceof Exceptions\HandlerException)
        {
            return;
        }

        if ( ! $this->config->get('understand-laravel.log_types.exception_log.enabled'))
        {
            return;
        }

        $exceptionArr = $this->encoder->exceptionToArray($exception);
        $exceptionArr['tags'] = ['exception_log'];
        $additional = $this->config->get('understand-laravel.log_types.exception_log.meta', []);

        $this->logger->log($exceptionArr, $additional);
    }
}