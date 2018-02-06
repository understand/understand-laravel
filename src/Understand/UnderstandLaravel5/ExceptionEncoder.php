<?php namespace Understand\UnderstandLaravel5;

use Exception;
use Throwable;
use InvalidArgumentException;
use SplFileObject;

class ExceptionEncoder
{

    /**
     * @var string
     */
    protected $projectRoot;

    /**
     * @param string $projectRoot
     */
    public function setProjectRoot($projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    /**
     * Serialize exception object
     *
     * @param mixed $exception
     * @return array
     */
    public function exceptionToArray($exception)
    {
        if ( ! ($exception instanceof Exception || $exception instanceof Throwable))
        {
            throw new InvalidArgumentException('$exception must be instance of Exception or Throwable');
        }

        $trace = $exception->getTrace();
        $className = get_class($exception);
        $message = $exception->getMessage() ? $exception->getMessage() : $className;

        return [
            'message' => $message,
            'class' => $className,
            'code' => $exception->getCode(),
            'file' => $this->removeProjectRoot($exception->getFile()),
            'line' => $exception->getLine(),
            'stack' => $this->stackTraceToArray($trace, $exception->getFile(), $exception->getLine())
        ];
    }

    /**
     * @param array $errorLog
     * @return array
     */
    public function setCurrentStackTrace(array $errorLog)
    {
        $level = isset($errorLog['level']) ? $errorLog['level'] : null;
        $stackTrace = $this->getCurrentStackTrace($level);
        $firstLineSet = false;

        foreach($stackTrace as $trace)
        {
            if ($firstLineSet)
            {
                break;
            }

            $firstLineSet = true;

            $errorLog['class'] = null;
            $errorLog['file'] = isset($trace['file']) ? $trace['file'] : null;
            $errorLog['line'] = isset($trace['line']) ? $trace['line'] : null;
        }

        $errorLog['stack'] = $stackTrace;

        return $errorLog;
    }

    /**
     * @return array
     */
    protected function getCurrentStackTrace()
    {
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 100);
        $vendorExcluded = false;

        foreach($stackTrace as $index => $trace)
        {
            // exclude Understand service provider and helper classes
            if (isset($trace['class']) && strpos($trace['class'], 'Understand\UnderstandLaravel5\\') === 0)
            {
                unset($stackTrace[$index]);
            }

            if ( ! isset($trace['file']))
            {
                $vendorExcluded = true;
            }

            if ($vendorExcluded)
            {
                continue;
            }

            // exclude `vendor` folder until project path reached
            if (strpos($trace['file'], $this->projectRoot . 'vendor' . DIRECTORY_SEPARATOR) === 0)
            {
                unset($stackTrace[$index]);
            }
            else
            {
                $vendorExcluded = true;
            }
        }

        return $this->stackTraceToArray($stackTrace);
    }

    /**
     * Serialize stack trace to array
     *
     * @param array $stackTrace
     * @return array
     */
    public function stackTraceToArray(array $stackTrace, $topFile = null, $topLine = null)
    {
        $stack = [];

        foreach ($stackTrace as $trace)
        {
            // Exception object `getTrace` does not return file and line number for the first line
            // http://php.net/manual/en/exception.gettrace.php#107563
            if (isset($topFile, $topLine) && $topFile && $topLine)
            {
                $trace['file'] = $topFile;
                $trace['line'] = $topLine;

                unset($topFile, $topLine);
            }

            $type = $this->stackTraceCallToString($trace);
            $args = $this->stackTraceArgsToArray($trace);

            $stack[] = [
                'class' => isset($trace['class']) ? $trace['class'] : null,
                'function' => isset($trace['function']) ? $trace['function'] : null,
                'args' => $args,
                'type' => $type,
                'file' => $this->getStackTraceFile($trace),
                'line' => $this->getStackTraceLine($trace),
                'code' => $this->getCode($this->getStackTraceFile($trace), $this->getStackTraceLine($trace)),
            ];
        }

        return $stack;
    }

    /**
     * @param $relativePath
     * @param $line
     * @param int $linesAround
     * @return array|void
     */
    public function getCode($relativePath, $line, $linesAround = 6)
    {
        if ( ! $relativePath || ! $line)
        {
            return;
        }

        $filePath = $this->projectRoot . $relativePath;

        try
        {
            $file = new SplFileObject($filePath);
            $file->setMaxLineLen(250);
            $file->seek(PHP_INT_MAX);
            $codeLines = [];

            $from = max(0, $line - $linesAround - 2);
            $to = min($line + $linesAround -1, $file->key() + 1);

            $file->seek($from);

            while ($file->key() < $to && ! $file->eof())
            {
                $file->next();
                // `key()` returns 0 as the first line
                $codeLines[] = [
                    'line' => $file->key() + 1,
                    'code' => rtrim($file->current())
                ];
            }

            return $codeLines;
        }
        catch (\Exception $e)
        {}
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
            return $this->removeProjectRoot($trace['file']);
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
            else if ($arg instanceof \__PHP_Incomplete_Class)
            {
                $params[] = 'object(__PHP_Incomplete_Class)'; 
            }
            else
            {
                $params[] = gettype($arg);
            }
        }

        return $params;
    }

    /**
     * @param $path
     * @return string
     */
    protected function removeProjectRoot($path)
    {
        if (substr($path, 0, strlen($this->projectRoot)) == $this->projectRoot)
        {
            return substr($path, strlen($this->projectRoot));
        }
    }
}
