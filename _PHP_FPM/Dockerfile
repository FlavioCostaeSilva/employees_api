FROM php:8.3-fpm

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update -y \
    && apt-get install -y git curl nano zip cron libmcrypt-dev libbz2-dev zlib1g-dev libzip-dev \
     libpq-dev libicu-dev libjpeg62-turbo-dev gnupg2 ghostscript apt-transport-https iputils-ping \
    && docker-php-ext-install -j$(nproc) intl pdo_mysql bcmath bz2 pcntl exif zip \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-source delete \
    #Others PHP Configurations
    && echo "expose_php = off" > /usr/local/etc/php/conf.d/expose_php.ini \
    && echo "log_errors = on" > /usr/local/etc/php/conf.d/log_errors.ini \
    && echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory_limit.ini \
    && echo "display_errors = on" > /usr/local/etc/php/conf.d/display_errors.ini \
    && echo "upload_max_filesize = 300M" > /usr/local/etc/php/conf.d/upload_max_filesize.ini \
    && echo "post_max_size = 300M" > /usr/local/etc/php/conf.d/post_max_size.ini \
    && echo "max_file_uploads = 400" > /usr/local/etc/php/conf.d/max_file_uploads.ini \
    && echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/conf.d/date_timezone.ini \
    && echo "error_log = /var/log/php/php_errors.log" > /usr/local/etc/php/conf.d/error_log.ini \
    && echo "session.save_handler = redis" > /usr/local/etc/php/conf.d/session-save-handler.ini \
    && echo "session.save_path = \"tcp://redis:6379?auth=CSRkhT4P3BWqrv5WRSkdVeezLQzD7CJe\""  \
      > /usr/local/etc/php/conf.d/session-save-path.ini

# XDebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Redis install
RUN pecl install -o -f redis \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis

# Allow Composer to be run as root
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_DISABLE_XDEBUG_WARN=1

# Setup the Nodejs, NPM & Composer
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs build-essential \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

RUN mkdir /home/www-data

CMD ["php-fpm"]