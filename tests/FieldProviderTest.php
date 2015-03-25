<?php

class FieldProviderTest extends PHPUnit_Framework_TestCase
{
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
}