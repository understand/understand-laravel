<?php

class TokenProviderTest extends PHPUnit_Framework_TestCase
{
    public function testRandomToken()
    {
        $tokenProvider = new Understand\UnderstandLaravel5\TokenProvider();
        $initialToken = $tokenProvider->getToken();

        $this->assertNotEmpty($initialToken);
        $this->assertSame($initialToken, $tokenProvider->getToken());

        $tokenProvider->generate();

        $this->assertNotSame($initialToken, $tokenProvider->getToken());
    }
}