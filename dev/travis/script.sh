#!/usr/bin/env bash

# run magento test suits
php bin/magento dev:tests:run unit
php bin/magento dev:tests:run integration
