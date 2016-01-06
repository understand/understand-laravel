<?php namespace Understand\UnderstandLaravel5;

class ExceptionEncoder
{

    /**
     * Serialize exception object
     *
     * @param \Exception $exception
     * @return type
     */
    public function exceptionToArray(\Exception $exception)
    {
        $trace = $exception->getTrace();
        $className = get_class($exception);
        $message = $exception->getMessage() ? $exception->getMessage() : $className;

        return [
            'message' => $message,
            'class' => $className,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack' => $this->stackTraceToArray($trace)
        ];
    }

    /**
     * Serialize stack trace to array
     *
     * @param array $stackTrace
     * @return array
     */
    public function stackTraceToArray(array $stackTrace)
    {
        $stack = [];

        foreach ($stackTrace as $trace)
        {
            $type = $this->stackTraceCallToString($trace);
            $args = $this->stackTraceArgsToArray($trace);

            $stack[] = [
                'class' => isset($trace['class']) ? $trace['class'] : null,
                'function' => isset($trace['function']) ? $trace['function'] : null,
                'args' => $args,
                'type' => $type,
                'file' => $this->getStackTraceFile($trace),
                'line' => $this->getStackTraceLine($trace)
            ];
        }

        return $stack;
    }

    /**
     * Return stack trace line number
     *
     * @param array $trace
     * @return mixed
     */
    protected function getStackTraceLine(array $trace)
    {
        if (isset($trace['line']))
        {
            return $trace['line'];
        }
    }

    /**
     * Return stack trace file
     *
     * @param array $trace
     * @return mixed
     */
    protected function getStackTraceFile(array $trace)
    {
        if (isset($trace['file']))
        {
            return $trace['file'];
        }
    }

    /**
     * Return call type
     *
     * @param array $trace
     * @return string
     */
    protected function stackTraceCallToString(array $trace)
    {
        if (! isset($trace['type']))
        {
            return 'function';
        }

        if ($trace['type'] == '::')
        {
            return 'static';
        }

        if ($trace['type'] == '->')
        {
            return 'method';
        }
    }

    /**
     * Serialize stack trace function arguments
     *
     * @param array $trace
     * @return array
     */
    protected function stackTraceArgsToArray(array $trace)
    {
        $params = [];

        if (! isset($trace['args']))
        {
            return $params;
        }

        foreach ($trace['args'] as $arg)
        {
            if (is_array($arg))
            {
                $params[] = 'array(' . count($arg) . ')';
            }
            else if (is_object($arg))
            {
                $params[] = get_class($arg);
            }
            else if (is_string($arg))
            {
                $params[] = 'string(' . $arg . ')';
            }
            else if (is_int($arg))
            {
                $params[] = 'int(' . $arg . ')';
            }
            else if (is_float($arg))
            {
                $params[] = 'float(' . $arg . ')';
            }
            else if (is_bool($arg))
            {
                $params[] = 'bool(' . ($arg ? 'true' : 'false') . ')';
            }
            else
            {
                $params[] = (string) $arg;
            }
        }

        return $params;
    }

}
