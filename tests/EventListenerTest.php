<?php

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

        $fieldProvider = $this->app['understand.field-provider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);

        $this->app['Psr\Log\LoggerInterface']->error($message);

        $this->assertSame($called, 1);
        $this->assertTrue($messageSame);
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

            $laravelLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.field-provider'];
        $handler = new CallbackHandler($callback);

        $this->app['understand.logger'] = new Logger($fieldProvider, $handler, false);
        
        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', 'Illuminate\Foundation\Exceptions\Handler');
            
        $exception = new \RuntimeException();
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

            $exceptionLogTag = in_array('exception_log', $decoded['tags'], true);
        };

        $fieldProvider = $this->app['understand.field-provider'];
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
        $token = $this->app['understand.token-provider']->getToken();
        $token2 = $this->app['understand.token-provider']->getToken();
            
        $this->assertNotEmpty($token2);
        $this->assertSame($token2, $token);
    }
}