FROM php:8.4-fpm-alpine3.22

WORKDIR /var/www/html

RUN apk update && apk upgrade

RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    linux-headers \
    autoconf \
    g++ \
    make \
    libpq-dev \
    fcgi \
    # postgresql-dev \
    # postgresql-libs \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql \
    && docker-php-ext-install sockets \
    && docker-php-ext-enable sockets \
    # && apk del autoconf g++ make linux-headers postgresql-dev \
    && rm -rf /var/lib/apk/lists/* \
    && rm -rf /var/cache/apk/*

# prepara o endpoint de healthcheck:
RUN echo "ping.path = /ping" >> "/usr/local/etc/php-fpm.d/www.conf"
RUN echo "ping.response = pong" >> "/usr/local/etc/php-fpm.d/www.conf"
# como testar manualmente:
# > docker exec -it {service-name} sh
# SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./docker/containers/php/setup.sh /usr/local/bin/setup.sh

RUN chmod +x /usr/local/bin/setup.sh

COPY ./application .

ENTRYPOINT ["/usr/local/bin/setup.sh"]
