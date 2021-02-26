FROM ubuntu:18.04

ENV TZ=Europe/Kiev
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update -y && apt-get install --no-install-recommends --no-install-suggests -y \
dialog \
apt-utils \
nginx \
php7.2 \
php7.2-fpm \
php7.2-mysql \
php7.2-cli \
php7.2-curl \
php7.2-gd \
php-tidy \
php7.2-zip \
php-mbstring \
mysql-client

COPY default /etc/nginx/sites-available/default

COPY services.sh /root/services.sh
COPY start_nginx.sh /root/start_nginx.sh
COPY start_php_fpm.sh /root/start_php_fpm.sh
RUN chmod +x /root/start* \
    && chmod +x /root/services.sh

COPY . /var/www/html

############ INITIAL APPLICATION SETUP #####################

WORKDIR /var/www/html
RUN chown -R www-data * \
    && chmod -R 755 *

EXPOSE 80
EXPOSE 443

VOLUME ["/var/www/html/", "/var/log/nginx/"]

WORKDIR /root/
CMD ./services.sh