<?php

use Illuminate\Foundation\Application;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Str;
use Understand\UnderstandLaravel5\Logger;
use Understand\UnderstandLaravel5\Handlers\CallbackHandler;

class EventListenerTest extends Orchestra\Testbench\TestCase
{

    /**
     * Setup service provider
     *
     * @param object $app
     * @return void
     */
    protected function getPackageProviders($app)
    {
        return ['Understand\UnderstandLaravel5\UnderstandLaravel5ServiceProvider'];
    }

    /**
     * Test event listener
     *
     * @return void
     */
    public function testEventListener()
    {
        $called = 0;
        $message = 'test message';
        $messageSame = false;
        $laravelLogTag = false;

        $callback = function($data) use(&$called, &$messageSame, &$laravelLogTag, $message)
        {
            $called++;
            $decoded = json_decode($data, true);

            $messageSame = $message === $decoded['message'];
            $laravelLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.fieldProvider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $this->app['Psr\Log\LoggerInterface']->error($message);

        $this->assertSame($called, 1);
        $this->assertTrue($messageSame);
    }

    /**
     * Test event listener
     *
     * @return void
     */
    public function testRegenerateToken()
    {
        $initialToken = $this->app['understand.tokenProvider']->getToken();

        $this->assertEquals($initialToken, $this->app['understand.tokenProvider']->getToken());

        $event = 'illuminate.queue.after';

        if (class_exists('Illuminate\Queue\Events\JobProcessing'))
        {
            $job = new \Illuminate\Queue\Jobs\SyncJob($this->app, 'test', 'sync', 'sync');
            $event = new JobProcessing('sync', $job, ['only 5.2 requires the third parameter']);
        }

        if (method_exists($this->app['events'], 'dispatch'))
        {
            $this->app['events']->dispatch($event);
        }
        else
        {
            $this->app['events']->fire($event);
        }

        $this->assertNotEmpty($initialToken);
        $this->assertNotEquals($initialToken, $this->app['understand.tokenProvider']->getToken());
    }

    /**
     * Test event listener
     *
     * @return void
     */
    public function testDataCollectorResetsToken()
    {
        $this->app['understand.dataCollector']->setInArray('test', 1);

        $this->assertEquals([1], $this->app['understand.dataCollector']->getByKey('test'));

        $event = 'illuminate.queue.after';

        if (class_exists('Illuminate\Queue\Events\JobProcessing'))
        {
            $job = new \Illuminate\Queue\Jobs\SyncJob($this->app, 'test', 'sync', 'sync');
            $event = new JobProcessing('sync', $job, ['only 5.2 requires the third parameter']);
        }

        if (method_exists($this->app['events'], 'dispatch'))
        {
            $this->app['events']->dispatch($event);
        }
        else
        {
            $this->app['events']->fire($event);
        }

        $this->assertEmpty($this->app['understand.dataCollector']->getByKey('test'));
    }

    /**
     * Test event listener
     *
     * @return void
     */
    public function testIgnoredLogsConfig()
    {
        $called = 0;

        $callback = function($data) use(&$called)
        {
            $called++;
        };

        $this->app['config']->set('understand-laravel.ignored_logs', ['debug', 'notice']);

        $handler = new CallbackHandler($callback);
        $this->app['understand.logger'] = new Logger($this->app['understand.fieldProvider'], $handler, false);

        // debug and notice should be ignored
        $this->app['Psr\Log\LoggerInterface']->debug('test');
        $this->app['Psr\Log\LoggerInterface']->notice('test');

        // error and alert should reach the logger
        $this->app['Psr\Log\LoggerInterface']->error('test');
        $this->app['Psr\Log\LoggerInterface']->alert('test');

        $this->assertSame($called, 2);
    }

    /**
     * Test error handler logging
     */
    public function testErrorHandlerExceptionLogging()
    {
        $called = 0;
        $messageSame = false;
        $laravelLogTag = false;

        $callback = function($data) use(&$called, &$messageSame, &$laravelLogTag)
        {
            $called++;
            $decoded = json_decode($data, true);

            $this->assertEquals('EventListenerTest', $decoded['stack'][0]['class']);

            $laravelLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.fieldProvider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', 'Illuminate\Foundation\Exceptions\Handler');

        $exception = new \RuntimeException('Test');
        $this->app['Illuminate\Foundation\Exceptions\Handler']->report($exception);

        $this->assertSame($called, 1);
    }

    /**
     * Test message tag
     *
     * @return void
     */
    public function testExceptionLogTag()
    {
        $called = 0;
        $exceptionLogTag = false;

        $callback = function($data) use(&$called, &$exceptionLogTag)
        {
            $called++;
            $decoded = json_decode($data, true);

            $exceptionLogTag = in_array('error_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.fieldProvider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $exception = new \RuntimeException('Test');
        $this->app['Psr\Log\LoggerInterface']->error($exception);

        $this->assertSame($called, 1);
        $this->assertTrue($exceptionLogTag);
    }

    /**
     * Test token provider values
     *
     * @return void
     */
    public function testTokenProviderValue()
    {
        $token = $this->app['understand.tokenProvider']->getToken();
        $token2 = $this->app['understand.tokenProvider']->getToken();

        $this->assertNotEmpty($token2);
        $this->assertSame($token2, $token);
    }

    /**
     * @return void
     */
    public function testLoggerMessageInteger()
    {
        $called = 0;
        $message = 123;
        $messageSame = false;
        $laravelLogTag = false;

        $callback = function($data) use(&$called, &$messageSame, &$laravelLogTag, $message)
        {
            $called++;
            $decoded = json_decode($data, true);

            $messageSame = (string)$message === $decoded['message'];
            $laravelLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.fieldProvider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $this->app['Psr\Log\LoggerInterface']->error($message);

        $this->assertSame($called, 1);
        $this->assertTrue($messageSame);
    }

    /**
     * @return void
     */
    public function testLoggerMessageBoolean()
    {
        $called = 0;
        $messageSame = false;
        $laravelLogTag = false;

        $callback = function($data) use(&$called, &$messageSame, &$laravelLogTag)
        {
            $called++;
            $decoded = json_decode($data, true);

            // `false` should be casted to `0`
            $messageSame = '0' === $decoded['message'];
            $laravelLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.fieldProvider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $this->app['Psr\Log\LoggerInterface']->error(false);

        $this->assertSame($called, 1);
        $this->assertTrue($messageSame);
    }

    /**
     * @return void
     */
    public function testLoggerMessageObject()
    {
        $called = 0;
        $object = new \Illuminate\Support\Fluent(['test' => 123]);
        $messageSame = false;
        $laravelLogTag = false;

        $callback = function($data) use(&$called, &$messageSame, &$laravelLogTag, $object)
        {
            $called++;
            $decoded = json_decode($data, true);

            $messageSame = $object->toJson() === $decoded['message'];
            $laravelLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.fieldProvider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $this->app['Psr\Log\LoggerInterface']->error($object);

        $this->assertSame($called, 1);
        $this->assertTrue($messageSame);
    }
}