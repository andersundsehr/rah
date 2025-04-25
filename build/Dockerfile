FROM pluswerk/php-dev:nginx-8.3-alpine AS build-micro-sfx

COPY ./build/build-micro-sfx.sh /app/build/build-micro-sfx.sh

RUN cd /app/build && ./build-micro-sfx.sh

FROM pluswerk/php-dev:nginx-8.3-alpine AS build

WORKDIR /app

RUN git config --global --add safe.directory /app

COPY composer.json composer.lock /app/

RUN composer install --no-scripts --no-plugins

COPY . /app/

RUN composer install

RUN ./vendor/bin/grumphp run

RUN composer test

RUN composer install --no-dev

ENV COMPOSER_BIN_DIR=/root/.composer/vendor/bin

RUN composer global require humbug/box

RUN /root/.composer/vendor/bin/box compile

COPY --from=build-micro-sfx /app/build/buildroot/bin/micro.sfx /app/build/buildroot/bin/micro.sfx

RUN cd /app/build && ./package-rah-bin.sh && /app/public/.rah/rah --version

FROM webdevops/php-nginx:8.3-alpine AS production

ARG LAST_GIT_TAG

WORKDIR /app

ENV WEB_DOCUMENT_ROOT=/app/public \
    RAH_STORAGE_PATH=/storage \
    RAH_MAX_DISK_USAGE=10GB

VOLUME /storage

COPY --from=build --chown=1000:1000 /app/bin/ /app/bin/
COPY --from=build --chown=1000:1000 /app/src/ /app/src/
COPY --from=build --chown=1000:1000 /app/vendor/ /app/vendor/
COPY --from=build --chown=1000:1000 /app/public/ /app/public/
COPY --from=build --chown=1000:1000 /app/templates/ /app/templates/
COPY --from=build --chown=1000:1000 /app/config/ /app/config/
COPY --from=build --chown=1000:1000 /app/.env.dev /app/composer.json /app/composer.lock /app/install.sh /app/
COPY --from=build --chown=1000:1000 /app/bin/startup-ensure-api-key-created.sh /opt/docker/provision/entrypoint.d/99-startup-ensure-api-key-created.sh

RUN echo "APP_ENV=prod" > .env
RUN echo "APP_DEBUG=0" >> .env
RUN echo "RAH_VERSION=$LAST_GIT_TAG" >> .env
