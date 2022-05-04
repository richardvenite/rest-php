FROM php:8.1.5-fpm-alpine

#Instaling my-sql driver
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libpq-dev libldap2-dev default-mysql-client zip libpq-dev libzip-dev git wget vim libcurl4-gnutls-dev\
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip pcntl


# Install xdebug
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer


#Create a new directory to run our app.
RUN mkdir -p /var/www/html/

#Set the new directory as our working directory
WORKDIR /var/www/html/

# Set user permissions
RUN mkdir -p /var/www/html/storage/framework/sessions && \
    mkdir -p /var/www/html/storage/framework/views && \
    mkdir -p /var/www/html/storage/framework/cache && \
    mkdir -p /var/www/html/storage/framework/testing && \
    mkdir -p /var/www/html/storage/app/public && \
    chmod -R 755 /var/www/html/storage && \
    chown -R ${USER}:${USER_GID} /var/www/html/

# RUN sed -i "s/www-data/$USER/" /usr/local/etc/php/www.conf

# USER ${USER}

#Our app runs on port 9000. Expose it!
EXPOSE 9000