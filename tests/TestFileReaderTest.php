<?php

use Understand\UnderstandLaravel5\ExceptionEncoder;
use PHPUnit\Framework\TestCase;

class TestFileReaderTest extends TestCase
{
    public function testCodeReader()
    {
        $projectRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $encoder = new ExceptionEncoder();
        $encoder->setProjectRoot($projectRoot);


        $result = $encoder->getCode('TestFile.txt', 5, 0);

        $this->assertTrue(isset($result[0]['code']));
        $this->assertSame('Line 4', $result[0]['code']);
    }

    public function testCodeReaderLinesAround()
    {
        $projectRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $encoder = new ExceptionEncoder();
        $encoder->setProjectRoot($projectRoot);


        $result = $encoder->getCode('TestFile.txt', 5, 2);

        foreach($result as $value)
        {
            $this->assertSame('Line ' . ($value['line'] - 1), $value['code']);
        }
    }
}