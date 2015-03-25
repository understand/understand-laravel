<?php namespace Understand\UnderstandLaravel5;

use Understand\UnderstandLaravel5\Logger;
use Illuminate\Database\Eloquent\Model;

class ModelEventListener
{

    /**
     * Logger instance
     *
     * @var Understand\UnderstandLaravel\Logger
     */
    protected $logger;

    /**
     * Additional fields
     *
     * @var array
     */
    protected $additionalFields;

    /**
     * @param Understand\UnderstandLaravel\Logger $logger
     *
     * @param \Understand\UnderstandLaravel\Logger $logger
     * @param array $additionalFields
     */
    public function __construct(Logger $logger, array $additionalFields)
    {
        $this->logger = $logger;
        $this->additionalFields = $additionalFields;
    }

    /**
     * Log model event
     *
     * @param string $eventName
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function logModelEvent($eventName, Model $model)
    {
        $log = [
            'id' => (int) $model->id,
            'model_event' => $eventName,
            'model_name' => get_class($model),
            'table_name' => $model->getTable(),
            'changes' => $model->getDirty(),
            'tags' => ['model_log']
        ];

        $this->logger->log($log, $this->additionalFields);
    }

}
