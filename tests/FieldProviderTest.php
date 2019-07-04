<?php

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Str;

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
        $this->app['understand.dataCollector']->setLimit(1);

        $this->app['understand.dataCollector']->setInArray('sql_queries', ['query' => 'select', 'bindings' => [1], 'time' => 1]);
        $this->app['understand.dataCollector']->setInArray('sql_queries', ['query' => 'select', 'bindings' => [1], 'time' => 1]);

        $queries = $this->app['understand.fieldProvider']->getSqlQueries();

        $this->assertCount(1, $queries);
    }

    public function testQueryBindings()
    {
        $this->app['understand.dataCollector']->setInArray('sql_queries', ['query' => 'SELECT 1 FROM users WHERE id = ?', 'bindings' => [123], 'time' => 1]);
        $queries = $this->app['understand.fieldProvider']->getSqlQueries();

        $this->assertEquals('SELECT 1 FROM users WHERE id = ?', $queries[0]['query']);
        $this->assertTrue(empty($queries[0]['bindings']));
    }

    public function testEnableQueryBindings()
    {
        $this->app['config']->set('understand-laravel.sql_bindings', true);

        $this->app['understand.dataCollector']->setInArray('sql_queries', ['query' => 'SELECT 1 FROM users WHERE id = ?', 'bindings' => [123], 'time' => 1]);
        $queries = $this->app['understand.fieldProvider']->getSqlQueries();

        $this->assertEquals('SELECT 1 FROM users WHERE id = 123', $queries[0]['query']);
        $this->assertTrue(empty($queries[0]['bindings']));
    }

    public function testGetServerIp()
    {
        \Illuminate\Support\Facades\Route::get('/', function() {});

        $this->call('GET', '/', [], [], [], ['SERVER_ADDR' => '127.0 0.1']);

        $ip = $this->app['understand.fieldProvider']->getServerIp();

        $this->assertEquals('127.0 0.1', $ip);
    }

    public function testQueryString()
    {
        \Illuminate\Support\Facades\Route::get('/test', function() {});

        $this->call('GET', '/test?query=123&password=1234');

        $queryString = $this->app['understand.fieldProvider']->getQueryStringArray();

        $this->assertEquals('123', $queryString['query']);
        $this->assertEquals('[value hidden]', $queryString['password']);
    }

    public function testPostRequestParameters()
    {
        \Illuminate\Support\Facades\Route::post('/', function() {});

        $this->call('POST', '/', ['test' => 'a', 'password' => 'b']);

        $postData = $this->app['understand.fieldProvider']->getPostDataArray();

        $this->assertEquals('a', $postData['test']);
        $this->assertEquals('[value hidden]', $postData['password']);
    }

    public function testJsonRequest()
    {
        if ( ! method_exists($this, 'json'))
        {
            return $this->markTestSkipped('The test base class does not support json requests');
        }

        \Illuminate\Support\Facades\Route::post('/', function() {});

        $this->json('POST', '/', ['test' => 'b', 'password' => 'test']);

        $jsonData = $this->app['understand.fieldProvider']->getPostDataArray();

        $this->assertEquals('b', $jsonData['test']);
        $this->assertEquals('[value hidden]', $jsonData['password']);
    }

    public function testQueryStringDisabled()
    {
        \Illuminate\Support\Facades\Route::get('/test', function() {});

        $this->app['config']->set('understand-laravel.query_string_enabled', false);

        $this->call('GET', '/test?query=123&password=1234');

        $queryString = $this->app['understand.fieldProvider']->getQueryStringArray();

        $this->assertTrue(empty($queryString));
    }

    public function testPostRequestParametersDisabled()
    {
        \Illuminate\Support\Facades\Route::post('/', function() {});

        $this->app['config']->set('understand-laravel.post_data_enabled', false);

        $this->call('POST', '/', ['test' => 'a', 'password' => 'b']);
        $postData = $this->app['understand.fieldProvider']->getPostDataArray();

        $this->assertTrue(empty($postData));
    }
  
    public function testGroupIdStaysTheSame()
    {
        $data = [
            'file' => 'app/Repositories/StripeWebhookRepository.php',
            'class' => null,
            'line' => 27
        ];

        $hash = $this->app['understand.fieldProvider']->getGroupId($data);
        $this->assertEquals('1c1cf65bd2685193dca31e321f976512a6f1ea32', $hash);

        $data['code'] = '0';

        $hash = $this->app['understand.fieldProvider']->getGroupId($data);
        $this->assertEquals('1c1cf65bd2685193dca31e321f976512a6f1ea32', $hash);
    }

    public function testGroupIdChangesWhenCodeChanges()
    {
        $data = [
            'file' => 'app/Repositories/StripeWebhookRepository.php',
            'class' => null,
            'line' => 27,
            'code' => 12
        ];

        $hash = $this->app['understand.fieldProvider']->getGroupId($data);
        $this->assertEquals('b500a4d4a363865e707c3bc95df8d4453e1ad8b1', $hash);
    }
}
