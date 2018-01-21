<?php

use Understand\UnderstandLaravel5\ExceptionEncoder;

class TestFileReaderTest extends PHPUnit_Framework_TestCase
{
    public function testCodeReader()
    {
        $projectRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $encoder = new ExceptionEncoder();
        $encoder->setProjectRoot($projectRoot);


        $result = $encoder->getCode('TestFile.txt', 5, 0);

        $this->assertTrue(isset($result[5]));
        $this->assertSame('Line 4', $result[5]);
    }

    public function testCodeReaderLinesAround()
    {
        $projectRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $encoder = new ExceptionEncoder();
        $encoder->setProjectRoot($projectRoot);


        $result = $encoder->getCode('TestFile.txt', 5, 2);

        foreach($result as $key => $value)
        {
            $this->assertSame('Line ' . ($key - 1), $value);
        }
    }
}