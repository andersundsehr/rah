#!/usr/bin/env bash

set -euo pipefail

echo "* * * * * cd $PWD && ./bin/cron.sh" | crontab -u application -

su -m application -c "./bin/console startup:ensure-api-key-created -vvv --ansi"
su -m application -c "./bin/console background:cleanup-old-deployments -vvv --ansi --print-table"
