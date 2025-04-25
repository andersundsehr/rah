#!/usr/bin/env bash

set -euo pipefail

if [ ! -f buildroot/bin/micro.sfx ]; then
  ./build-micro-sfx.sh
fi

#./spc micro:combine ../rah.phar -O ../public/.rah/rah
cat buildroot/bin/micro.sfx ../rah.phar > ../public/.rah/rah

echo " file: rah"
echo " size: $(du -h ../public/.rah/rah | cut -f1)"
echo "../public/.rah/rah -h"
