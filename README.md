![Logo](https://dev.heidelpay.de/devHeidelpay_400_180.jpg)

# Heidelpay payment extension for Magento2

This extension for Magento 2 provides a direct integration of the Heidelpay payment methods to your Magento 2 shop. 

Currently supported payment methods are:
* Credit Card
* Debit Card
* Sofortüberweisung
* PayPal
* Prepayment
* giropay

For more information please visit -https://dev.heidelpay.de/magento2/

## SYSTEM REQUIREMENTS

This extension requires PHP 5.6 or PHP 7.0. 
It also depends on the Heidelpay php-api library, which will be installed along with the plugin.  

## LICENSE

You can find a copy of this license in [LICENSE.md](LICENSE.md).

## Release notes

All versions greater than 16.10.17 are based on the heidelpay php-api. (https://github.com/heidelpay/php-api). Please visit https://dev.heidelpay.de/PhpApi/ for the developer documentation.

## Installation


### Install the heidelpay Magento 2 composer package

```composer require "heidelpay/magento2:17.2.7"```

### Enable the extension in Magento 2

```php -f bin/magento module:enable --clear-static-content Heidelpay_Gateway```

### Setup the extension and refresh cache

```php -f bin/magento setup:upgrade```

```php -f bin/magento cache:flush```

```php -f bin/magento setup:di:compile```

```php -f bin/magento setup:static-content:deploy```

and you are ready to go.
