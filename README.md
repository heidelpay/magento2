![Logo](https://dev.heidelpay.de/devHeidelpay_400_180.jpg)

# Heidelpay payment extension for magento2

This extension for Magento 2 provides a direct integration of the Heidelpay payment methods to your Magento 2 shop. 

Currently supported payment methods are:
* credit card
* debit card
* Sofortüberweisung
* PayPal
* prepayment

For more information please visit - https://dev.heidelpay.de/shopmodule/magento/magento-2-x/

### Installation

add composer repository

composer config repositories.heidelpay composer https://heidelpay.de/packages

install packages

composer  require "heidelpay/php-api:16.10.27"
composer  require "heidelpay/magento2:16.10.27"

enable extension in Magento

php -f bin/magento module:enable --clear-static-content Heidelpay_Gateway


setup and refresh cache

php -f bin/magento setup:upgrade

php -f bin/magento cache_flush

php -f bin/magento setup:di:compile
 
php -f bin/magento setup:static-content:deploy 

and you are ready to go.

### SYSTEM REQUIREMENTS

This extension requires PHP 5.6 or PHP7.0. 
It also depends on the Heidelpay php-api library.   

### LICENSE

You can find a copy of this license in [LICENSE.md](LICENSE.md).

### Release notes

All versions greater than 16.10.17 are based on the heidelpay php-api. (https://github.com/heidelpay/php-api). Please visit https://dev.heidelpay.de/PhpApi/ for the developer documentation.

