<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace GuzzleAdapterTest;

use Porta\Billing\Guzzle\ConfigGuzzle;
use Porta\Billing\Cache\InstanceCache;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Tests for ConfigGuzzle
 *
 */
class ConfigGuzzleTest extends \PHPUnit\Framework\TestCase
{

    const HOST = 'testhost.dom';
    const ACCOUNT = [
        ConfigGuzzle::LOGIN => 'testUser',
        ConfigGuzzle::PASSWORD => 'testPass',
    ];

    public function testConfig()
    {
        $config = new ConfigGuzzle(self::HOST, new InstanceCache, self::ACCOUNT);
        $request = $config->getBaseApiRequest();
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals(self::HOST, $request->getUri()->getHost());
        $this->assertInstanceOf(StreamInterface::class, $config->getStream());
        $this->assertInstanceOf(InstanceCache::class, $config->getCache());
        $this->assertEquals(self::ACCOUNT, $config->getAccount());
    }
}
