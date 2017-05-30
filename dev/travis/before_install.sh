#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# disable xdebug and adjust memory limit
echo '==> Disabling xdebug, adjusting memory limit to -1.'
phpenv config-rm xdebug.ini
phpenv rehash;
