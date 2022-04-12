<?php

use Understand\UnderstandLaravel5\Logger;
use Understand\UnderstandLaravel5\Handlers\CallbackHandler;
use Understand\UnderstandLaravel5\UnderstandLaravel5ServiceProvider;

class LogFilterTest extends Orchestra\Testbench\TestCase
{

    /**
     * Setup service provider
     *
     * @param object $app
     * @return void
     */
    protected function getPackageProviders($app)
    {
        return [UnderstandLaravel5ServiceProvider::class];
    }

    /**
     * @return void
     */
    public function testLogFilterAllowsDelivery()
    {
        $logsSent = 0;

        $callback = function() use(&$logsSent)
        {
            $logsSent++;
        };

        $this->app['config']->set('understand-laravel.log_filter', function() {
            // FALSE, logs should not be filtered
            return false;
        });

        $handler = new CallbackHandler($callback);
        $this->app['understand.logger'] = new Logger($this->app['understand.fieldProvider'], $handler);

        // trigger error
        $this->app['Psr\Log\LoggerInterface']->error('test');
        $this->app['Psr\Log\LoggerInterface']->warning('test2');

        $this->assertEquals(2, $logsSent);
    }

    /**
     * @return void
     */
    public function testServiceContainerDependency()
    {
        $logsSent = 0;

        $callback = function() use(&$logsSent)
        {
            $logsSent++;
        };

        $dependencyName = 'service-container-dependency';
        $dependencyCalled = false;

        $this->app->bind($dependencyName, function() use(&$dependencyCalled) {
            return function() use(&$dependencyCalled) {
                $dependencyCalled = true;
                // FALSE, logs should not be filtered
                return false;
            };
        });

        $this->app['config']->set('understand-laravel.log_filter', $dependencyName);

        $handler = new CallbackHandler($callback);
        $this->app['understand.logger'] = new Logger($this->app['understand.fieldProvider'], $handler);

        // trigger error
        $this->app['Psr\Log\LoggerInterface']->error('test');

        $this->assertTrue($dependencyCalled);
        $this->assertEquals(1, $logsSent);
    }

    /**
     * @return void
     */
    public function testLogFilterFiltersOneLog()
    {
        $logsSent = 0;

        $callback = function() use(&$logsSent)
        {
            $logsSent++;
        };

        $this->app['config']->set('understand-laravel.log_filter', function($level, $message, $context) {
            if ($message === 'test2') {
                // TRUE, log should be filtered
                return true;
            }

            // FALSE, logs should not be filtered
            return false;
        });

        $handler = new CallbackHandler($callback);
        $this->app['understand.logger'] = new Logger($this->app['understand.fieldProvider'], $handler);

        // trigger error
        $this->app['Psr\Log\LoggerInterface']->error('test');
        $this->app['Psr\Log\LoggerInterface']->warning('test2');

        $this->assertEquals(1, $logsSent);
    }

    /**
     * @return void
     */
    public function testLogFilterReceivesAllData()
    {
        $logsSent = 0;

        $callback = function() use(&$logsSent)
        {
            $logsSent++;
        };

        $this->app['config']->set('understand-laravel.log_filter', function($level, $message, $context) {
            $this->assertEquals('error', $level);
            $this->assertEquals('test', $message);
            $this->assertEquals(['context' => 'value'], $context);

            // FALSE, logs should not be filtered
            return false;
        });

        $handler = new CallbackHandler($callback);
        $this->app['understand.logger'] = new Logger($this->app['understand.fieldProvider'], $handler);

        // trigger error
        $this->app['Psr\Log\LoggerInterface']->error('test', ['context' => 'value']);

        $this->assertEquals(1, $logsSent);
    }
}