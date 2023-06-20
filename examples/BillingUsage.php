<?php

/*
 * PortaOne Billing JSON API wrapper usage example, Guzzle client
 */
require __DIR__ . '/../vendor/autoload.php';

use Porta\Billing\Billing;
use Porta\Billing\Guzzle\ConfigGuzzle;
use Porta\Billing\BulkOperation;

// Create Guzzle config object
$config = new ConfigGuzzle(
        // Billing host (bill-sip server)
        'my-porta-one-server.com',
        // Will use simple php class instance cache object as we need no sesson persistance
        new \Porta\Billing\Cache\InstanceCache(),
        // And finally credentials to access billing API
        [ConfigGuzzle::LOGIN => 'myLogin', ConfigGuzzle::PASSWORD => 'myPass']
);

// Create the API wrapper
$billing = new Billing($config);

//Load first 50 customers from the server
echo "Starting load customer records\n";
$t = microtime(true);
$answer = $billing->call('/Customer/get_customer_list', ['limit' => 50]);
$customers = $answer['customer_list'];

echo "Loaded " . count($customers) . " customer records in " . (microtime(true) - $t) . " seconds\n";

// remove account from config object to show how it may work from stored session
$config->setAccount();

// Re-create billing object without account. But the session snjred in the cache class instance.
$billing = new Billing($config);

// Preapare bulk load of their accounts
/** @var BulkOperation[] $requests */
$requests = [];
foreach ($customers as $customer) {
    $customerId = $customer['i_customer'];
    $requests[$customerId] = new BulkOperation('Account/get_account_list', ['i_customer' => $customerId]);
}

// Bulk load of accounts. With Guzzle we can do it in conurrent mode, much faster
$t = microtime(true);
$billing->callConcurrent($requests);
echo "Complete " . count($requests) . " calls in " . (microtime(true) - $t) . " seconds\n";

//Print out results
foreach ($customers as $customer) {
    /** @var BulkOperation $request */
    $request = $requests[$customer['i_customer']];

    echo "Customer '{$customer['name']}' has ";
    if (!$request->success()) {
        echo "error loading account data: " . $request->getException()->getMessage() . "\n";
        continue;
    }
    $accounts = $request->getResponse()['account_list'];
    echo count($accounts) . " account(s).\n";
    foreach ($accounts as $account) {
        echo "    Account ID: " . $account['id'] . "\n";
    }
}

// And now let's do it async
//Sad but true, Guzzle not really async (as PHP itself), at least before it works
//with React or other event loop. In fact, first call only be send on the first ->wait(),
//so you may consider it concurrent with unset concurrency limit.
// However, callbacks and promise chaining work, so it may sense to prepare some
// billing calls with it's callbacks and then runs all the collected promises in
// parallel. As a result, all calbacks will be fired and all answers processed.
echo "\n\nNow will try 50 requests async\n";
$t = microtime(true);

// Run async calls and collect promises, it will NOT really send calls at this moment
$promises = [];
foreach ($customers as $customer) {
    $customerId = $customer['i_customer'];
    $promises[$customerId] = $billing->callAsync(
                    'Account/get_account_list',
                    ['i_customer' => $customerId])
            // Wrap the promose with function which will print data immediately as it appears,
            // with timestamp
            ->then(
            function (array $result) use ($t) {
                echo sprintf('%1.3f', microtime(true) - $t) . " Loaded: ";
                $list = [];
                if (isset($result['account_list'])) {
                    foreach ($result['account_list'] as $account) {
                        $list[] = $account['id'];
                    }
                }
                echo ([] == $list) ? "nothing\n" : implode(', ', $list) . "\n";
                return $result;
            }
    );
}

// Use Guzzle utility to ->wait() for each promose and unwrap result into array
// If could we wrap the promoses with ->then() and set callbacks, it could start
// to fire as each call finiheed, just after unwrap();
echo "T,s   Result\n";
$responses = \GuzzleHttp\Promise\Utils::unwrap($promises);
// And $responses populated with all the answers

echo "Complete " . count($responses) . " calls in " . (microtime(true) - $t) . " seconds\n";

// That's all folks!


