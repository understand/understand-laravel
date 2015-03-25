<?php namespace Understand\UnderstandLaravel5\Facades;

use Illuminate\Support\Facades\Facade;

class UnderstandExceptionLogger extends Facade
{

    /**
     * Return facade loc accessor
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'understand.exceptionLogger';
    }

}
