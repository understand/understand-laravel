<?php

class ExceptionEncoderTest extends PHPUnit_Framework_TestCase
{
    public function testExceptionObjectResult()
    {
        $code = 1234;
        $message = 'Test Message';
        $exception = new \DomainException($message, $code);

        $encoder = new Understand\UnderstandLaravel5\ExceptionEncoder();
        $exceptionArray = $encoder->exceptionToArray($exception);
        $stackTraceArray = $encoder->stackTraceToArray($exception->getTrace());

        $this->assertSame($message, $exceptionArray['message']);
        $this->assertSame('DomainException', $exceptionArray['class']);
        $this->assertSame($code, $exceptionArray['code']);
        $this->assertSame(__FILE__, $exceptionArray['file']);
        $this->assertSame(serialize($stackTraceArray), serialize($exceptionArray['stack']));
        $this->assertNotEmpty($exceptionArray['line']);
    }

    public function testStackTraceResult()
    {
        $exception = new \DomainException;

        $encoder = new Understand\UnderstandLaravel5\ExceptionEncoder();
        $originalStackTrace = $exception->getTrace();
        $stackTraceArray = $encoder->stackTraceToArray($originalStackTrace);

        $this->assertSame(count($originalStackTrace), count($stackTraceArray));
        $this->assertSame($originalStackTrace[0]['function'], $stackTraceArray[0]['function']);
        $this->assertSame($originalStackTrace[0]['class'], $stackTraceArray[0]['class']);
    }

    public function testEmptyExceptionMessageCase()
    {
        $exception = new \DomainException;
        $encoder = new Understand\UnderstandLaravel5\ExceptionEncoder();
        $exceptionArray = $encoder->exceptionToArray($exception);

        $this->assertSame('DomainException', $exceptionArray['message']);
    }

    public function testStackTraceSerializationWithoutArgs()
    {
        $stackTrace = debug_backtrace();
        unset($stackTrace[0]['args']);

        $encoder = new Understand\UnderstandLaravel5\ExceptionEncoder();
        $stackTraceArray = $encoder->stackTraceToArray($stackTrace);

        $this->assertEmpty($stackTraceArray[0]['args']);
    }
}
