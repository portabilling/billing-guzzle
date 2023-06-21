<?php

/*
 * PortaOne Billing JSON API wrapper, Guzzle bindings
 * See main package <https://packagist.org/packages/porta/billing>
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest;

use Porta\Billing\Billing;
use Porta\Billing\Guzzle\ConfigGuzzle;
use Porta\Billing\Cache\InstanceCache;
use GuzzleHttp\RequestOptions as RO;

/**
 * Description of LiveTest
 *
 */
class Live extends \PHPUnit\Framework\TestCase
{

    protected static Billing $billing;

    const CONFIG_TEMPLATE = <<< EOT
<?php
use Porta\Billing\Config as C;
use GuzzleHttp\RequestOptions as RO;

\$host = 'billing-sip-host.dom';
\$account = [
    C::LOGIN => 'userName',
    C::PASSWORD => 'userPass',
    //Uncomment TOKEN and comment PASSWORD if you wish to use token
    //C::TOKEN => 'userToken',
];
\$options = [
    RO::VERIFY => false,
];
EOT;
    const CONFIG_FILE = __DIR__ . '/temp/live-test-config.php';
    const NO_CONFIG_MESSAGE = "Live test need config file with host and account\n"
            . "Config file created from template at:\n" . self::CONFIG_FILE
            . "\nPlease fill the template with host and account data and run live test again.\n"
            . "Do not forget to remove the file with credentials after test finished!";

    public static function setUpBeforeClass(): void
    {
        if (!file_exists(self::CONFIG_FILE)) {
            file_put_contents(self::CONFIG_FILE, self::CONFIG_TEMPLATE);
            static::fail(self::NO_CONFIG_MESSAGE);
        }
        require self::CONFIG_FILE;
        if ($host == 'billing-sip-host.dom' || $account['login'] == 'userName') {
            static::fail(self::NO_CONFIG_MESSAGE);
        }
        try {
            self::$billing = new Billing(new ConfigGuzzle($host, new InstanceCache(), $account, $options));
        } catch (\Porta\Billing\Exceptions\PortaException $ex) {
            self::fail("Can't init billing class with provied config.\nError: {$ex->getMessage()}");
        }
    }

    public function testGetCustomers()
    {
        $result = self::$billing->call('Customer/get_customer_list', ['limit' => 50]);
        $this->assertArrayHasKey('customer_list', $result);
        return $result['customer_list'];
    }

    /**
     * @depends testGetCustomers
     */
    public function testGetCustomerInfo($customerList)
    {
        $list = [];
        foreach ($customerList as $customer) {
            $list[] = new \Porta\Billing\BulkOperation('Customer/get_customer_info', ['i_customer' => $customer['i_customer']]);
        }
        self::$billing->callConcurrent($list);
        foreach ($list as $item) {
            if (!$item->success()) {
                throw $item->getException();
            }
            $this->assertTrue($item->success());
            $this->assertArrayHasKey('customer_info', $item->getResponse());
        }
    }

    public function testGetCountries()
    {
        $result = self::$billing->call('Generic/get_countries_list', []);
        $this->assertArrayHasKey('countries_list', $result);
    }

    public function testLogout()
    {
        self::$billing->logout();
        $this->assertFalse(self::$billing->isSessionPresent());
    }
}
