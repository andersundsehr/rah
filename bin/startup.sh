#!/usr/bin/env bash

set -euo pipefail

echo "* * * * * cd $PWD && ./bin/cron.sh" | crontab -u application -

./bin/console startup:ensure-api-key-created -vvv --ansi
