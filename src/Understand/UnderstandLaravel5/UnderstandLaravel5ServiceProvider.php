<?php namespace Understand\UnderstandLaravel5;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Str;
use Illuminate\Foundation\Application;
use Blade;
use Exception;
use Throwable;
use Understand\UnderstandLaravel5\Facades\UnderstandJsProvider;

class UnderstandLaravel5ServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        $configPath = __DIR__ . '/../../config/understand-laravel.php';
        $this->publishes([$configPath => config_path('understand-laravel.php')], 'config');
        $enabled = $this->app['config']->get('understand-laravel.enabled');

        if ($enabled)
        {
            $this->listenLaravelEvents();
        }

        if ($enabled && $this->app['config']->get('understand-laravel.sql_enabled'))
        {
            $this->listenQueryEvents();
        }

        $this->registerBladeDirectives();
	}

    /**
     * Register Blade directives.
     *
     * @return void
     */
	protected function registerBladeDirectives()
    {
        // L5.0 does not support custom directives
        if ($this->detectLaravelVersion(['5.0']))
        {
            Blade::extend(function ($view, $compiler)
            {
                $configuration = UnderstandJsProvider::getJsConfig();

                $pattern = $compiler->createMatcher('understandJsConfig');

                return preg_replace($pattern, json_encode($configuration), $view);
            });

            Blade::extend(function ($view, $compiler)
            {
                $configuration = UnderstandJsProvider::getJsConfig();

                $pattern = $compiler->createMatcher('understandJs');

                $out  = "<script src=\"" . UnderstandJsProvider::getJsBundleUrl() . "\"></script>\r\n";
                $out .= "<script>\r\n";
                $out .= "Understand.init(" . json_encode($configuration) . ");\r\n";
                $out .= "Understand.installErrorHandlers();\r\n";
                $out .= "</script>";

                return preg_replace($pattern, $out, $view);
            });
        }
        else
        {
            Blade::directive('understandJsConfig', function ()
            {
                $configuration = UnderstandJsProvider::getJsConfig();

                return json_encode($configuration);
            });

            Blade::directive('understandJs', function ()
            {
                $configuration = UnderstandJsProvider::getJsConfig();

                $out  = "<script src=\"" . UnderstandJsProvider::getJsBundleUrl() . "\"></script>\r\n";
                $out .= "<script>\r\n";
                $out .= "Understand.init(" . json_encode($configuration) . ");\r\n";
                $out .= "Understand.installErrorHandlers();\r\n";
                $out .= "</script>";

                return $out;
            });
        }
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerConfig();
        $this->registerFieldProvider();
        $this->registerJsProvider();
        $this->registerDataCollector();
        $this->registerTokenProvider();
        $this->registerLogger();
        $this->registerExceptionEncoder();
        $this->registerEventLoggers();
	}

    /**
     * Register config
     *
     * @return void
     */
    protected function registerConfig()
    {
        $configPath = __DIR__ . '/../../config/understand-laravel.php';
        $this->mergeConfigFrom($configPath, 'understand-laravel');
    }

    /**
     * Register field provider
     *
     * @return void
     */
    protected function registerFieldProvider()
    {
        $this->app->bind('understand.fieldProvider', function($app)
        {
            $fieldProvider = new FieldProvider();

            if ($app['config']['session.driver'])
            {
                $fieldProvider->setSessionStore($app['session.store']);
            }

            $fieldProvider->setRouter($app['router']);
            $fieldProvider->setRequest($app['request']);
            $fieldProvider->setEnvironment($app->environment());
            $fieldProvider->setTokenProvider($app['understand.tokenProvider']);
            $fieldProvider->setDataCollector($app['understand.dataCollector']);
            $fieldProvider->setApp($app);

            return $fieldProvider;
        });

        $this->app->booting(function()
        {
            $loader = AliasLoader::getInstance();
            $loader->alias('UnderstandFieldProvider', 'Understand\UnderstandLaravel5\Facades\UnderstandFieldProvider');
        });
    }

    /**
     * Register JS provider
     *
     * @return void
     */
    protected function registerJsProvider()
    {
        $this->app->bind('understand.jsProvider', function($app)
        {
            return new JsProvider($app);
        });

        $this->app->booting(function()
        {
            $loader = AliasLoader::getInstance();
            $loader->alias('UnderstandJsProvider', 'Understand\UnderstandLaravel5\Facades\UnderstandJsProvider');
        });
    }

    /**
     * Register token generator class
     *
     * @return void
     */
    protected function registerTokenProvider()
    {
        $this->app->singleton('understand.tokenProvider', function()
        {
            return new TokenProvider();
        });
    }

    /**
     * Register data collector class
     *
     * @return void
     */
    protected function registerDataCollector()
    {
        $this->app->singleton('understand.dataCollector', function()
        {
            return new DataCollector();
        });
    }

    /**
     * Register exception encoder
     *
     * @return void
     */
    protected function registerExceptionEncoder()
    {
        $this->app->bind('understand.exceptionEncoder', function()
        {
            return new ExceptionEncoder;
        });
    }

    /**
     * Register exception and event logger
     *
     * @return void
     */
    protected function registerEventLoggers()
    {
        $this->app->bind('understand.eventLogger', function($app)
        {
            return new EventLogger($app['understand.logger'], $app['config']);
        });

        $this->app->bind('understand.exceptionLogger', function($app)
        {
            return new ExceptionLogger($app['understand.logger'], $app['understand.exceptionEncoder'], $app['config']);
        });

        $this->app->booting(function()
        {
            $loader = AliasLoader::getInstance();
            $loader->alias('UnderstandExceptionLogger', 'Understand\UnderstandLaravel5\Facades\UnderstandExceptionLogger');
        });
    }

    /**
     * Register understand logger
     *
     * @return void
     */
    protected function registerLogger()
    {
        $this->app->singleton('understand.logger', function($app)
        {
            $fieldProvider = $app['understand.fieldProvider'];
            $handler = $this->resolveHandler($app);

            return new Logger($fieldProvider, $handler);
        });

        $this->app->booting(function()
        {
            $loader = AliasLoader::getInstance();
            $loader->alias('UnderstandLogger', 'Understand\UnderstandLaravel5\Facades\UnderstandLogger');
        });
    }

    /**
     * Return default handler
     *
     * @param type $app
     * @return mixed
     * @throws \ErrorException
     */
    protected function resolveHandler($app)
    {
        $inputToken = $app['config']->get('understand-laravel.token');

        $apiUrl = $app['config']->get('understand-laravel.url', 'https://api.understand.io');
        $handlerType = $app['config']->get('understand-laravel.handler');
        $sslBundlePath = $app['config']->get('understand-laravel.ssl_ca_bundle');

        if ($handlerType == 'async')
        {
            return new Handlers\AsyncHandler($inputToken, $apiUrl, $sslBundlePath);
        }

        if ($handlerType == 'sync')
        {
            return new Handlers\SyncHandler($inputToken, $apiUrl, $sslBundlePath);
        }

        throw new \ErrorException('understand-laravel handler misconfiguration:' . $handlerType);
    }
    
    /**
     * Detect Laravel version
     * 
     * @param array $versions
     * @return type
     */
    protected function detectLaravelVersion(array $versions)
    {
        return Str::startsWith(Application::VERSION, $versions);
    }
    
    /**
     * Listen Laravel logs and queue events
     *
     * @return void
     */
    protected function listenLaravelEvents()
    {
        // only Laravel versions below L5.4 supports `illuminate.log`
        if ($this->detectLaravelVersion(['5.0', '5.1', '5.2', '5.3']))
        {
            $this->app['events']->listen('illuminate.log', function($level, $message, $context)
            {
                $this->handleEvent($level, $message, $context);
            });
        }
        else
        {
            // starting from L5.4 MessageLogged event class was introduced
            // https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072
            $this->app['events']->listen('Illuminate\Log\Events\MessageLogged', function($log) 
            {

                $this->handleEvent($log->level, $log->message, $log->context);
            });
        }

        // starting from L5.2 JobProcessing event class was introduced
        // https://github.com/illuminate/queue/commit/ce2b5518902b1bcb9ef650c41900fc8c6392eb0c
        if ($this->app->runningInConsole())
        {
            if ($this->detectLaravelVersion(['5.0', '5.1']))
            {
                $this->app['events']->listen('illuminate.queue.after', function()
                {
                    $this->app['understand.tokenProvider']->generate();
                    $this->app['understand.dataCollector']->reset();
                });

                $this->app['events']->listen('illuminate.queue.failed', function()
                {
                    $this->app['understand.tokenProvider']->generate();
                    $this->app['understand.dataCollector']->reset();
                });
            }
            else
            {
                $this->app['events']->listen('Illuminate\Queue\Events\JobProcessing', function()
                {
                    $this->app['understand.tokenProvider']->generate();
                    $this->app['understand.dataCollector']->reset();
                });
            }
        }
    }

    /**
     * Listen Query events
     *
     * @return void
     */
    protected function listenQueryEvents()
    {
        // only Laravel versions below L5.2 supports `illuminate.query`
        if ($this->detectLaravelVersion(['5.0', '5.1']))
        {
            // $this->events->fire('illuminate.query', [$query, $bindings, $time, $this->getName()]);
            $this->app['events']->listen('illuminate.query', function($query, $bindings, $time)
            {
                $this->app['understand.dataCollector']->setInArray('sql_queries', [
                    'query' => $query,
                    'bindings' => $bindings,
                    'time' => $time,
                ]);
            });
        }
        else
        {
            // https://laravel.com/api/5.3/Illuminate/Database/Events/QueryExecuted.html
            $this->app['events']->listen('Illuminate\Database\Events\QueryExecuted', function($event)
            {
                $this->app['understand.dataCollector']->setInArray('sql_queries', [
                    'query' => $event->sql,
                    'bindings' => $event->bindings,
                    'time' => $event->time,
                ]);
            });
        }
    }

    /**
     * Handle a new log event
     * 
     * @param string $level
     * @param mixed $message
     * @param array $context
     * @return void
     */
    protected function handleEvent($level, $message, $context)
    {
        if ($this->shouldIgnoreEvent($level, $message, $context))
        {
            return;
        }

        // `\Log::info`, `\Log::debug` and NOT `\Exception` or `\Throwable`
        if (in_array($level, ['info', 'debug']) && ! ($message instanceof Exception || $message instanceof Throwable))
        {
            $this->app['understand.eventLogger']->logEvent($level, $message, $context);
        }
        // `\Log::notice`, `\Log::warning`, `\Log::error`, `\Log::critical`, `\Log::alert`, `\Log::emergency` and `\Exception`, `\Throwable`
        // '5.5', '5.6', '5.7', '5.8'
        else if ( ! $this->detectLaravelVersion(['5.0', '5.1', '5.2', '5.3', '5.4']) && isset($context['exception']) && ($context['exception'] instanceof Exception || $context['exception'] instanceof Throwable))
        {
            $exception = $context['exception'];
            unset($context['exception']);

            $this->app['understand.exceptionLogger']->logError($level, $exception, $context);
        }
        else
        {
            $this->app['understand.exceptionLogger']->logError($level, $message, $context);
        }
    }

    /**
     * @param $level
     * @param $message
     * @param $context
     * @return bool
     */
    protected function shouldIgnoreEvent($level, $message, $context)
    {
        $ignoredEventTypes = (array)$this->app['config']->get('understand-laravel.ignored_logs');

        if ( ! $ignoredEventTypes)
        {
            return false;
        }

        return in_array($level, $ignoredEventTypes, true);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'understand.fieldProvider',
            'understand.logger',
            'understand.exceptionEncoder',
            'understand.exceptionLogger',
            'understand.eventLogger',
            'understand.tokenProvider',
            'understand.dataCollector',
        ];
    }
}
