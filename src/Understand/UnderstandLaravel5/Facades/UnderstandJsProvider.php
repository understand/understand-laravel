<?php namespace Understand\UnderstandLaravel5\Facades;

use Illuminate\Support\Facades\Facade;

class UnderstandJsProvider extends Facade
{

    /**
     * Return facade loc accessor
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'understand.jsProvider';
    }
}