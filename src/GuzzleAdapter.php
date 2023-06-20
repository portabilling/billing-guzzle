<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Guzzle;

use Porta\Billing\Interfaces\ClientAdapterInterface;
use Porta\Billing\Exceptions\PortaException;
use Porta\Billing\Exceptions\PortaConnectException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Client adaptor for Guzzle library
 *
 * <https://docs.guzzlephp.org/en/stable/index.html>
 *
 * @package Guzzle
 * @api
 */
class GuzzleAdapter implements ClientAdapterInterface
{

    protected Client $client;

    /**
     * Setups Guzzle params for client
     *
     * @param array $guzzleOptions Guzzle client options
     *
     * See <https://docs.guzzlephp.org/en/stable/request-options.html>
     *
     * Opton HTTP_ERRORS will be enforced to false for this lib work
     * @api
     */
    public function __construct(array $guzzleOptions)
    {
        $this->client = new Client(array_merge($guzzleOptions, ['http_errors' => false]));
    }

    public function concurrent(iterable $requests, int $concurency = 20): array
    {
        $result = [];
        $pool = new Pool($this->client, $requests, [
            'concurrency' => $concurency,
            'fulfilled' => function (ResponseInterface $response, $index) use (&$result) {
                $result[$index] = $response;
            },
            'rejected' => function (RequestException $reason, $index) use (&$result) {
                $result[$index] = self::convertException($reason);
            },
        ]);
        $pool->promise()->wait();
        return $result;
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->send($request);
        } catch (GuzzleException $exception) {
            throw self::convertException($exception);
        }
    }

    public function sendAsync(RequestInterface $request)
    {
        return $this->client->sendAsync($request)
                        ->then(
                                null,
                                function (\Exception $reason) {
                                    throw self::convertException($reason);
                                }
        );
    }

    protected static function convertException(\Throwable $exception)
    {
        if ($exception instanceof ConnectException) {
            return new PortaConnectException($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof GuzzleException) {
            return new PortaException($exception->getMessage(), $exception->getCode());
        } else {
            return $exception;
        }
    }
}
