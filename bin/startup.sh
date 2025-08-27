#!/usr/bin/env bash

set -exuo pipefail

echo "* * * * * cd $PWD && ./bin/cron.sh" | crontab -u application -
chown -R application:application $RAH_STORAGE_PATH

# skip if vendor folder is not present
if [ ! -d "vendor" ]; then
    echo "Vendor folder not found, skipping startup commands."
    return
fi

su -m application -c "./bin/console startup:ensure-api-key-created -vvv --ansi"
su -m application -c "./bin/console background:cleanup-old-deployments -vvv --ansi --print-table"
