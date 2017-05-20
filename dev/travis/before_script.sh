#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

mkdir -p "$HOME/.php-cs-fixer"

# go into the parent folder and pull a full magento 2 ce project, to do all tests.
cd ..
composer self-update
composer create-project "magento/community-edition:$magento" magento-ce
cd "magento-ce"

# create database and move db config into place
echo '==> Creating database and moving db config into place'
mysql -uroot -e '
    SET @@global.sql_mode = NO_ENGINE_SUBSTITUTION;
    CREATE DATABASE magento_integration_tests;
'
mv etc/install-config-mysql.travis.php.dist etc/install-config-mysql.php

php bin/magento setup:install -q --admin-user="admin" --admin-password="123123q" --admin-email="admin@example.com" --admin-firstname="John" --admin-lastname="Doe"

# require the heidelpay extension to make it usable (autoloading)
echo "==> Requiring heidelpay/magento2 from the dev-$TRAVIS_BRANCH branch"
composer require "heidelpay/magento2:dev-$TRAVIS_BRANCH"

# enable the extension, do other relavant mage tasks.
echo "==> Enable extension, do mage tasks..."
php -f bin/magento module:enable Heidelpay_Gateway
php -f bin/magento setup:upgrade
php -f bin/magento cache:flush
php -f bin/magento setup:di:compile
php -f bin/magento dev:tests:run

# go into the actual cloned repo to do make preparations for the EQP tests.
echo "==> Doing preperations for EQP tests."
cd ../magento2
composer update
./vendor/bin/phpcs --config-set installed_paths vendor/magento/marketplace-eqp
