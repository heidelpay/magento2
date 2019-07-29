[![Latest Version on Packagist](https://img.shields.io/packagist/v/heidelpay/magento2.svg?style=flat-square)](https://packagist.org/packages/heidelpay/magento2)
[![Codacy Badge](https://api.codacy.com/project/badge/grade/fb5b516ad21f44a591a58761a8c3ef42)](https://www.codacy.com/app/heidelpay/magento2/dashboard)
[![PHP 5.6](https://img.shields.io/badge/php-5.6-blue.svg)](http://www.php.net)
[![PHP 7.0](https://img.shields.io/badge/php-7.0-blue.svg)](http://www.php.net)
[![PHP 7.1](https://img.shields.io/badge/php-7.1-blue.svg)](http://www.php.net)

![Logo](http://dev.heidelpay.com/devHeidelpay_400_180.jpg)

# Heidelpay payment extension for Magento2

This extension for Magento 2 provides a direct integration of the Heidelpay payment methods to your Magento 2 shop. 

Currently supported payment methods are:
* Credit Card
* Debit Card
* Direct Debit
* Direct Debit (Secured) (B2C)
* Sofort.
* PayPal
* Prepayment
* Invoice
* Invoice (Secured) (B2C)
* giropay
* iDeal
* Santander Hire Purchase

For more information please visit -http://dev.heidelpay.com/magento2/

## SYSTEM REQUIREMENTS

This extension requires PHP 5.6 or PHP 7.0. 
It also depends on the Heidelpay php-payment-api library, which will be installed along with the plugin.  

## LICENSE

You can find a copy of this license in [LICENSE.txt](LICENSE.txt).

## Release notes

All versions greater than 16.10.17 are based on the heidelpay php-api. (https://github.com/heidelpay/php-api).
All versions greater than 18.3.1 are based on the heidelpay php-payment-api. (https://github.com/heidelpay/php-payment-api). Please visit https://dev.heidelpay.com/PhpPaymentApi/ for the developer documentation.


## Installation


### Install the heidelpay Magento 2 composer package

```composer require "heidelpay/magento2"```

### Enable the extension in Magento 2

```php -f bin/magento module:enable Heidelpay_Gateway --clear-static-content```

### Setup the extension and refresh cache

```php -f bin/magento setup:upgrade```

```php -f bin/magento cache:flush```

```php -f bin/magento setup:di:compile```

```php -f bin/magento setup:static-content:deploy```

and you are ready to go.

## Support
For any issues or questions please get in touch with our support.

#### Web page
https://dev.heidelpay.com/
 
#### Email
support@heidelpay.com
 
#### Phone
+49 (0)6221/6471-100

#### Twitter
@devHeidelpay
