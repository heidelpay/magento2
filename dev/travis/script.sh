#!/usr/bin/env bash

php bin/magento dev:tests:run unit
php bin/magento dev:tests:run integration
