<?php

/*
 * PortaOne Billing JSON API wrapper, Guzzle bindings
 * See main package <https://packagist.org/packages/porta/billing>
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Guzzle;

use Psr\SimpleCache\CacheInterface;
use Porta\Billing\Guzzle\GuzzleAdapter;

/**
 * Guzzle-specific config class
 *
 * Setups wrapper for use Guzzle client and others components given
 *
 * @api
 * @package Guzzle
 */
class ConfigGuzzle extends \Porta\Billing\Config
{

    /**
     * Setup configuration object
     *
     * @param string $host Hostname/IP address of the server, no slashes, no schema,
     * but port if required. Example: `bill-sip.mycompany.com`
     * @param CacheInterface $cache SimpleCache object to persist session data
     * @param array|null $account Account record to login to the billing. Combination
     * of login+password or login+token required
     * ```
     * $account = [
     *     'login' => 'myUserName',    // Mandatory username
     *     'password' => 'myPassword', // When login with password
     *     'token' => 'myToken'        // When login with API token
     * ```
     * @param array $guzzleOptions oprions, passed to Guzzle HTTP client.
     *
     * Check [Guzzle docs](https://docs.guzzlephp.org/en/stable/request-options.html)
     *
     * Please, mind that Guzze options only apply at the moment of config class
     * creation by it's __construct(), so if options set **after** the wrapper
     * created - it will not be used.
     *
     * Щешщт HTTP_ERRORS will be enforced to false for this lib works.
     *
     * @throws Porta\Billing\Exceptions\PortaAuthException
     * @package Guzzle
     * @api
     */
    public function __construct(
            string $host,
            CacheInterface $cache,
            ?array $account = null,
            array $guzzleOptions = [])
    {
        $factory = new \GuzzleHttp\Psr7\HttpFactory();
        parent::__construct($host, $factory, $factory,
                new GuzzleAdapter($guzzleOptions), $cache, $account);
    }
}
