#!/usr/bin/env bash

echo '==> Doing phpcs tests with MEQP2 Standard setting.'
./vendor/bin/phpcs . --ignore=vendor/ --standard=MEQP2;

cd ../magento-ce
echo '==> Doing unit tests.';
php bin/magento dev:tests:run unit

echo '==> Perform Magento integration tests.'
php bin/magento dev:tests:run integration

echo '==> Prepare Custom integration tests.'
cp vendor/heidelpay/magento2/phpunit.xml.dist .dev/tests/integration/phpunit.xml.dist

echo '==> Perform Custom integration tests.'
php bin/magento dev:tests:run integration
