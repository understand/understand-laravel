<?php

use Orchestra\Testbench\TestCase;

class JsProviderTest extends TestCase
{
    protected $cdn = 'https://understand.cdn.io/bundle.min.js';

    /**
     * Setup service provider
     *
     * @param object $app
     * @return void
     */
    protected function getPackageProviders($app)
    {
        return [\Understand\UnderstandLaravel5\UnderstandLaravel5ServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('understand-laravel.token', '123456');
        $app['config']->set('understand-laravel.cdn', $this->cdn);
    }

    public function testJsBundleUrl()
    {
        $url = $this->app['understand.jsProvider']->getJsBundleUrl();

        $this->assertSame($this->cdn, $url);
    }

    public function testJsConfiguration()
    {
        $configuration = $this->app['understand.jsProvider']->getJsConfig();

        $this->assertArrayHasKey('env', $configuration);
        $this->assertEquals('testing', $configuration['env']);

        $this->assertArrayHasKey('token', $configuration);
        $this->assertEquals('123456', $configuration['token']);

        $this->assertArrayHasKey('context', $configuration);
        $this->assertArrayHasKey('session_id', $configuration['context']);
        $this->assertArrayHasKey('request_id', $configuration['context']);
        $this->assertArrayHasKey('user_id', $configuration['context']);
        $this->assertArrayHasKey('client_ip', $configuration['context']);
    }
}
