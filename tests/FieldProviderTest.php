<?php

use Illuminate\Foundation\AliasLoader;

class FieldProviderTest extends Orchestra\Testbench\TestCase
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
    
    public function testExtend()
    {
        $fieldProvider = new \Understand\UnderstandLaravel5\FieldProvider();
        $method = 'getTestValue';
        $value = 'tets value';
        $this->assertFalse(method_exists($fieldProvider, $method));

        $fieldProvider->extend($method, function() use($value)
        {
            return $value;
        });

        $this->assertSame($value, $fieldProvider->{$method}());
    }

    public function testLaravelAuth()
    {
        $userId = 23452345;
        
        \Illuminate\Support\Facades\Auth::shouldReceive('id')->once()->andReturn($userId);
        
        $currentUserId = $this->app['understand.fieldProvider']->getUserId();
        
        $this->assertSame($userId, $currentUserId);
    }
    
    public function testSentinelGetUser()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Sentinel', '\Illuminate\Support\Facades\Auth');
        
        $user = new stdClass();
        $user->id = 423523;
        
        \Illuminate\Support\Facades\Auth::shouldReceive('getUser')->once()->andReturn($user);
        
        $currentUserId = $this->app['understand.fieldProvider']->getUserId();

        $this->assertSame($user->id, $currentUserId);
    }
        
    public function testSentryGetUser()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Sentry', '\Illuminate\Support\Facades\Auth');
        
        $user = new stdClass();
        $user->id = 545;
        
        \Illuminate\Support\Facades\Auth::shouldReceive('getUser')->once()->andReturn($user);
        
        $currentUserId = $this->app['understand.fieldProvider']->getUserId();

        $this->assertSame($user->id, $currentUserId);
    }

    public function testFieldProviderThrowsAnException()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Sentry', '\Illuminate\Support\Facades\Auth');

        \Illuminate\Support\Facades\Auth::shouldReceive('getUser')->andThrow('Exception');

        $currentUserId = $this->app['understand.fieldProvider']->getUserId();

        $this->assertNull($currentUserId);
    }

    public function testQueryCount()
    {
        $this->app['understand.dataCollector']->set('sql_queries', range(1, 1000));

        $queries = $this->app['understand.fieldProvider']->getSqlQueries();

        $this->assertCount(100, $queries);
    }

    public function testGetServerIp()
    {
        $this->app['understand.dataCollector']->set('sql_queries', range(1, 1000));

        $this->call('GET', '/', [], [], [], ['SERVER_ADDR' => '127.0 0.1']);

        $ip = $this->app['understand.fieldProvider']->getServerIp();

        $this->assertEquals('127.0 0.1', $ip);
    }
}
