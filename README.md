## Laravel 5 service provider for Understand.io

[![Build Status](https://travis-ci.org/understand/understand-laravel5.svg)](https://travis-ci.org/understand/understand-laravel5)
[![Latest Stable Version](https://poser.pugx.org/understand/understand-laravel5/v/stable.svg)](https://packagist.org/packages/understand/understand-laravel5) 
[![Latest Unstable Version](https://poser.pugx.org/understand/understand-laravel5/v/unstable.svg)](https://packagist.org/packages/understand/understand-laravel5) 
[![License](https://poser.pugx.org/understand/understand-laravel5/license.svg)](https://packagist.org/packages/understand/understand-laravel5)

### Introduction

This packages provides a full abstraction for Understand.io and provides extra features to improve Laravel's default logging capabilities. It is essentially a wrapper around Laravel's event handler to take full advantage of Understand.io's data aggregation and analysis capabilities.

### Quick start

1. Add this package to your composer.json

    ```php
    "understand/understand-laravel5": "0.0.*"
    ```

2. Update composer.json packages
    
    ```
    composer update understand/understand-laravel5
    ```

3. Add the ServiceProvider to the providers array in app/config/app.php
  
    ```php
    'Understand\UnderstandLaravel5\UnderstandLaravel5ServiceProvider',
    ```

4. Publish configuration file

    ```
    php artisan vendor:publish --provider="Understand\UnderstandLaravel5\UnderstandLaravel5ServiceProvider"
    ```

5. Open ```app/Exceptions/Handler.php``` and put this line ```\UnderstandExceptionLogger::log($e)``` inside ```report``` method.
  
  ```php
  /**
   * Report or log an exception.
   *
   * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
   *
   * @param  \Exception  $e
   * @return void
   */
  public function report(Exception $e)
  {
      \UnderstandExceptionLogger::log($e);

      return parent::report($e);
  }
  ```

6. Set your input key in config file (```config/understand-laravel.php```)
  
    ```php
    'token' => 'my-input-token'
    ```

6. Send your first event

    ```php 
    // anywhere inside your Laravel app
    \Log::info('Understand.io test');
    ```
