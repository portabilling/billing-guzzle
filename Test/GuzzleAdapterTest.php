<?php

/*
 * PortaOne Billing JSON API wrapper, Guzzle bindings
 * See main package <https://packagist.org/packages/porta/billing>
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace GuzzleAdapterTest;

use Porta\Billing\Guzzle\GuzzleAdapter;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use Porta\Billing\Exceptions\PortaConnectException;
use Porta\Billing\Exceptions\PortaException;

/**
 * Description of GuzzleAdapterTest
 *
 */
class GuzzleAdapterTest extends \PHPUnit\Framework\TestCase
{

    protected $container = [];

    protected function prepareRequests(array $answers)
    {
        $mock = new MockHandler($answers);
        $handlerStack = HandlerStack::create($mock);
        $this->container = [];
        $handlerStack->push(Middleware::history($this->container));

        return ['handler' => $handlerStack];
    }

    protected function getRequst($index): ?Request
    {
        return $this->container[$index]['request'] ?? null;
    }

    public function testSend()
    {
        $request = (new Request('POST', 'TestUri'))
                ->withHeader('User-Agent', 'TestAgent');
        $adapter = new GuzzleAdapter(
                $this->prepareRequests(
                        [
                            new Response(200),
                            new Response(500),
                            new ConnectException("ConnectMessage", $request),
                            new TransferException('TransferException', 0),
                        ]
                )
        );

        $this->assertEquals(200, $adapter->send($request)->getStatusCode());
        $this->assertEquals($request, $this->getRequst(0));
        $this->assertEquals(500, $adapter->send($request)->getStatusCode());
        try {
            $adapter->send($request);
            $this->fail("Missed exception");
        } catch (\Throwable $exc) {
            $this->assertInstanceOf(PortaConnectException::class, $exc);
        }
        try {
            $adapter->send($request);
            $this->fail("Missed exception");
        } catch (\Throwable $exc) {
            $this->assertInstanceOf(PortaException::class, $exc);
        }
    }

    public function testConcurrent()
    {
        $request = (new Request('POST', 'TestUri'))
                ->withHeader('User-Agent', 'TestAgent');
        $adapter = new GuzzleAdapter(
                $this->prepareRequests(
                        [
                            new Response(200),
                            new ConnectException("ConnectMessage", $request),
                            new TransferException('TransferException', 0),
                            new \ErrorException(''),
                        ]
                )
        );

        foreach (['200', 'connect', 'transfer', 'error'] as $key) {
            $requests[$key] = $request;
        }
        $responses = $adapter->concurrent($requests);

        $this->assertEquals(200, $responses['200']->getStatusCode());
        $this->assertInstanceOf(PortaConnectException::class, $responses['connect']);
        $this->assertInstanceOf(PortaException::class, $responses['transfer']);
        $this->assertInstanceOf(\Exception::class, $responses['transfer']);
    }

    public function testSendAsync()
    {
        $request = (new Request('POST', 'TestUri'))
                ->withHeader('User-Agent', 'TestAgent');
        $adapter = new GuzzleAdapter(
                $this->prepareRequests(
                        [
                            new Response(200),
                            new ConnectException("ConnectMessage", $request),
                            new TransferException('TransferException', 0),
                            new \ErrorException(''),
                        ]
                )
        );

        $response = $adapter->sendAsync($request)->wait();
        $this->assertEquals(200, $response->getStatusCode());
        try {
            $adapter->sendAsync($request)->wait();
            $this->fail("Missed exception");
        } catch (\Throwable $exc) {
            $this->assertInstanceOf(PortaConnectException::class, $exc);
        }
        try {
            $adapter->sendAsync($request)->wait();
            $this->fail("Missed exception");
        } catch (\Throwable $exc) {
            $this->assertInstanceOf(PortaException::class, $exc);
        }
        $this->expectException(\Exception::class);
        $adapter->sendAsync($request)->wait();
    }
}
