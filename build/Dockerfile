FROM webdevops/php-nginx-dev:8.3-alpine AS build

WORKDIR /app

RUN git config --global --add safe.directory /app

COPY . /app/

RUN composer install

#RUN ./vendor/bin/grumphp run

#RUN composer test

RUN composer install --no-dev

RUN composer global require humbug/box

RUN /root/.composer/vendor/bin/box compile

RUN cd ./build && ./package-rah-bin.sh && cd -

FROM webdevops/php-nginx:8.3-alpine AS production

ARG LAST_GIT_TAG

WORKDIR /app

ENV WEB_DOCUMENT_ROOT=/app/public \
    RAH_STORAGE_PATH=/storage

VOLUME /storage

COPY --from=build --chown=1000:1000 /app/src/ /app/src/
COPY --from=build --chown=1000:1000 /app/vendor/ /app/vendor/
COPY --from=build --chown=1000:1000 /app/public/ /app/public/
COPY --from=build --chown=1000:1000 /app/templates/ /app/templates/
COPY --from=build --chown=1000:1000 /app/config/ /app/config/
COPY --from=build --chown=1000:1000 /app/.env.dev /app/composer.json /app/composer.lock /app/install.sh /app/

RUN echo "APP_ENV=prod" > .env
RUN echo "APP_DEBUG=0" >> .env
RUN echo "RAH_VERSION=$LAST_GIT_TAG" >> .env
