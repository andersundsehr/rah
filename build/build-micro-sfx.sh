#!/usr/bin/env bash

set -euo pipefail

if [ ! -f spc ]; then
  curl -fsSL -o spc.tgz https://github.com/crazywhalecc/static-php-cli/releases/download/2.8.5/spc-linux-x86_64.tar.gz
  echo '523ba4279c54c7a377156c0dd3a36adf92ee64b01e9a7f5e9e2ec084b8e458e5  spc.tgz' | sha256sum -c -
  tar -zxvf spc.tgz
  rm spc.tgz
  chmod +x spc
fi

EXTENSIONS=pcntl,ctype,zip,xml,exif,iconv,mbstring,phar,sockets,zlib,tokenizer,filter,openssl,curl

./spc doctor --auto-fix
./spc install-pkg upx

./spc download --with-php=8.5 --for-extensions $EXTENSIONS

./spc build --with-micro-fake-cli --with-upx-pack --build-micro $EXTENSIONS

echo "Build completed successfully!"
echo " file: buildroot/bin/micro.sfx"
echo " size: $(du -h buildroot/bin/micro.sfx | cut -f1)"
