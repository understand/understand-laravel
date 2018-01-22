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
}