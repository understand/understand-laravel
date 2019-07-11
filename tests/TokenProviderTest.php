<?php

use Orchestra\Testbench\TestCase;

class TokenProviderTest extends TestCase
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