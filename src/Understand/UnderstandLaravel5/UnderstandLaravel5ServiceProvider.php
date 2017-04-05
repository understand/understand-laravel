<?php namespace Understand\UnderstandLaravel5;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Str;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model as EloquentModel;

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

        if ($this->app['config']->get('understand-laravel.log_types.eloquent_log.enabled'))
        {
            $this->listenEloquentEvents();
        }

        if ($this->app['config']->get('understand-laravel.log_types.laravel_log.enabled'))
        {
            $this->listenLaravelEvents();
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
        $this->registerTokenProvider();
        $this->registerLogger();
        $this->registerModelEventListenerProvider();
        $this->registerExceptionEncoder();
        $this->registerExceptionLogger();
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
        $this->app->bind('understand.field-provider', function($app)
        {
            $fieldProvider = new FieldProvider();

	    if ($app['config']['session.driver']) 
	    {
                $fieldProvider->setSessionStore($app['session.store']);
            }
            $fieldProvider->setRouter($app['router']);
            $fieldProvider->setRequest($app['request']);
            $fieldProvider->setEnvironment($app->environment());
            $fieldProvider->setTokenProvider($app['understand.token-provider']);

            return $fieldProvider;
        });

        $this->app->booting(function()
        {
            $loader = AliasLoader::getInstance();
            $loader->alias('UnderstandFieldProvider', 'Understand\UnderstandLaravel5\Facades\UnderstandFieldProvider');
        });
    }

    /**
     * Register token generator class
     *
     * @return void
     */
    protected function registerTokenProvider()
    {
        $this->app->singleton('understand.token-provider', function()
        {
            return new TokenProvider();
        });
    }

    /**
     * Register exception encoder
     *
     * @return void
     */
    protected function registerExceptionEncoder()
    {
        $this->app->bind('understand.exception-encoder', function()
        {
            return new ExceptionEncoder;
        });
    }

    /**
     * Register exception logger
     *
     * @return void
     */
    protected function registerExceptionLogger()
    {
        $this->app->bind('understand.exceptionLogger', function($app)
        {
            $logger = $app['understand.logger'];
            $encoder = $app['understand.exception-encoder'];

            return new ExceptionLogger($logger, $encoder, $app['config']);
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
            $fieldProvider = $app['understand.field-provider'];
            $handler = $this->resolveHandler($app);
            $silent = $app['config']->get('understand-laravel.silent');

            return new Logger($fieldProvider, $handler, $silent);
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
        $silent = $app['config']->get('understand-laravel.silent');
        $handlerType = $app['config']->get('understand-laravel.handler');
        $sslBundlePath = $app['config']->get('understand-laravel.ssl_ca_bundle');

        if ($handlerType == 'async')
        {
            return new Handlers\AsyncHandler($inputToken, $apiUrl, $silent, $sslBundlePath);
        }

        if ($handlerType == 'sync')
        {
            return new Handlers\SyncHandler($inputToken, $apiUrl, $silent, $sslBundlePath);
        }

        if ($handlerType == 'queue')
        {
            return new Handlers\LaravelQueueHandler($inputToken, $apiUrl, $silent, $sslBundlePath);
        }

        throw new \ErrorException('understand-laravel handler misconfiguration:' . $handlerType);
    }

    /**
     * Register model event listener provider
     *
     * @return void
     */
    protected function registerModelEventListenerProvider()
    {
        $this->app->bind('understand.model-event-listener-provider', function($app)
        {
            $logger = $app['understand.logger'];
            $additional = $this->app['config']->get('understand-laravel.log_types.eloquent_log.meta', []);

            return new ModelEventListener($logger, $additional);
        });
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
     * Listen Laravel logs
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
        $log = [];
        
        if ($message instanceof Exceptions\HandlerException)
        {
            return;
        }
        else if ($message instanceof \Exception)
        {
            $log = $this->app['understand.exception-encoder']->exceptionToArray($message);
            $log['tags'] = ['exception_log'];
        }
        // // integer, float, string or boolean as message
        else if (is_scalar($message))
        {
            $log['message'] = $message;
            $log['tags'] = ['laravel_log'];
        }
        else
        {
            $log = (array)$message;
            $log['tags'] = ['laravel_log'];
        }

        if ($context)
        {
            $log['context'] = $context;
        }

        $log['level'] = $level;

        $additional = $this->app['config']->get('understand-laravel.log_types.laravel_log.meta', []);
        $this->app['understand.logger']->log($log, $additional);
    }
    
    /**
     * Listen eloquent model events and log them
     *
     * @return void
     */
    protected function listenEloquentEvents()
    {
        $modelLogger = $this->app['understand.model-event-listener-provider'];

        $events = [
            'eloquent.created*' => 'created',
            'eloquent.updated*' => 'updated',
            'eloquent.deleted*' => 'deleted',
            'eloquent.restored*' => 'restored',
        ];

        foreach ($events as $listenerName => $eventName)
        {
            if ($this->detectLaravelVersion(['5.0', '5.1', '5.2', '5.3']))
            {
                $this->app['events']->listen($listenerName, function($model) use($modelLogger, $eventName)
                {
                    $modelLevelEventName = 'eloquent.' . $eventName . ': ' . get_class($model);
                    
                    $modelLogger->logModelEvent($eventName, $model, $modelLevelEventName);
                });
            }
            else
            {
                $this->app['events']->listen($listenerName, function($modelLevelEventName, $eventPayload) use($modelLogger, $eventName)
                {
                    if (isset($eventPayload[0]) && $eventPayload[0] instanceof EloquentModel)
                    {
                        $modelLogger->logModelEvent($eventName, $eventPayload[0], $modelLevelEventName);
                    }
                });
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'understand.field-provider',
            'understand.logger',
            'understand.model-event-listener-provider',
            'understand.exception-encoder',
            'understand.exceptionLogger'
        ];
    }
}
