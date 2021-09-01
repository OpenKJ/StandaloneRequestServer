# DO NOT EDIT: created by update.sh from Dockerfile-debian.template
FROM php:8.0-apache-bullseye

# entrypoint.sh and cron.sh dependencies
RUN set -ex; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        busybox-static \
        git \
    ; \
    rm -rf /var/lib/apt/lists/*; \

# install the PHP extensions we need
# see https://docs.nextcloud.com/server/stable/admin_manual/installation/source_installation.html
ENV PHP_MEMORY_LIMIT 512M
ENV PHP_UPLOAD_LIMIT 512M
RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libmemcached-dev \
    ; \
    \
    debMultiarch="$(dpkg-architecture --query DEB_BUILD_MULTIARCH)"; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-configure ldap --with-libdir="lib/$debMultiarch"; \
    docker-php-ext-install -j "$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
    ; \
    \
# pecl will claim success even if one install fails, so we need to perform each install separately
    pecl install APCu-5.1.20; \
    pecl install memcached-3.1.5; \
    \
    docker-php-ext-enable \
        apcu \
        memcached \
    ; \
    rm -r /tmp/pear; \
    \
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

# set recommended PHP.ini settings

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.revalidate_freq=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    \
    echo 'apc.enable_cli=1' >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini; \
    \
    { \
        echo 'memory_limit=${PHP_MEMORY_LIMIT}'; \
        echo 'upload_max_filesize=${PHP_UPLOAD_LIMIT}'; \
        echo 'post_max_size=${PHP_UPLOAD_LIMIT}'; \
    } > /usr/local/etc/php/conf.d/openkj.ini; \
    \
    copy *.php /var/www/html; \
    copy *.inc /var/www/html; \
    copy *.css /var/www/html; \
    rm -rf /var/www/Dockerfile; \
    chown -R www-data:root /var/www; \
    chmod -R g=u /var/www

VOLUME /var/www/html

RUN a2enmod headers rewrite remoteip ;\
    {\
     echo RemoteIPHeader X-Real-IP ;\
     echo RemoteIPTrustedProxy 10.0.0.0/8 ;\
     echo RemoteIPTrustedProxy 172.16.0.0/12 ;\
     echo RemoteIPTrustedProxy 192.168.1.0/16 ;\
    } > /etc/apache2/conf-available/remoteip.conf;\
    a2enconf remoteip

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
