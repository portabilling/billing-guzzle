# PortaOne Billing API and ESPF wrapper for PHP, with Guzzle

## Purpose

This package includes Guzzle bindings for [porta/billing API wrapper](https://github.com/portabilling/billing) library and intended to make the billing lib easy to use with Guzzle. Please, refer to main package docs on use of the API wrapper. Just use `ConfigGuzzle` class as a configuration object instead of `Config`.

Using Guzzle allows faster high-volume concurent calls and async calls.

With this package library will use Guzzle PSR-7 implementation and client, these dependencies are hardcoded into ConfigGuzzle class. You still need to supply [PSR-16](https://www.php-fig.org/psr/psr-16) SimpleCache object to save session data and provide session persistance. Very basic implementations packaged into main lib, enough to handle session storage in not too high load ineronment.

## Usage
Start with [usage example](https://github.com/portabilling/billing-guzzle/blob/master/examples/BillingUsage.php), check [phpDoc](https://portabilling.github.io/billing-guzzle/).

However, the primary source of usage information is the core [porta/billing](https://github.com/portabilling/billing) package, please refer to it's docs.

## Installation
In the Composer storage. Just add proper require section:
```
    "require": {
        "porta/billing-guzzle": "^0.1"
    }
```
Please, review the [changelog](https://github.com/portabilling/billing/blob/master/CHANGELOG.MD) before to change used version.

## Dependencies
Composer dependencies:
- php: ^7.4|^8.0|^8.1|^8.2
- porta/billing: ^0.1
- guzzlehttp/guzzle": ^7.2

Mind no need to put core lib in the dependency separately, it will load by composer.

## Testing
Tested with PHPUnit 9.6 on php 7.4 to 8.2, 100% coverage, which does not mean everything will work as intended.

Current testing and compatibility status check on [Github flows](https://github.com/portabilling/billing-guzzle/actions/workflows/Compatibility.yml)

To test, install in dev mode and use: 
- `composer test` command from library root for run all standard modular tests
- `composer livetest` to run a test against live PortaOne billing server if you have one available.

For live testing run `livetest` once and it will create config file templeate. Then edit the file to provide host, username and password or token and run `livetest` again. Do not forget to remove the config file after tests!

Manualy tested and used with PortaBilling release MR100.