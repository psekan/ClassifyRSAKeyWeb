FROM alpine:3.6
MAINTAINER peter.sekan11@gmail.com

ENV TIMEZONE Europe/Prague
ENV MYSQL_DATABASE rsa
ENV MYSQL_PASSWORD changeMe
ENV MYSQL_DATAPATH "/var/lib/mysql"

RUN apk add --update apache2 mysql mysql-client openssl \
    php7-apache2 \
    php7-bcmath \
    php7-cli php7-curl php7-common php7-ctype \
    php7-fileinfo \
    php7-iconv \
    php7-json \
    php7-opcache php7-openssl \
    php7-pdo php7-pdo_mysql php7-pdo_sqlite php7-phar \
    php7-session php7-simplexml php7-soap php7-sqlite3 \
    php7-tokenizer \
    php7-xml php7-xmlreader php7-xmlwriter \
    php7-zip \
    wget \
    && rm -rf /var/cache/apk/* && rm -rf /var/www/localhost/www/*

RUN addgroup mysql mysql

ADD etc/apache2/httpd.conf /etc/apache2/httpd.conf
COPY www /var/www/localhost/www
COPY app /var/www/localhost/app
COPY log /var/www/localhost/log
COPY temp /var/www/localhost/temp
ADD composer.json /var/www/localhost/
ADD composer.lock /var/www/localhost/
ADD composer-post-install.php /var/www/localhost/
ADD cli.php /var/www/localhost/
ADD run.sh /run.sh

RUN mkdir -p /run/apache2 && \
    chown apache:apache -R /var/www/localhost/www && \
    wget https://getcomposer.org/composer.phar && \
    mv composer.phar /usr/bin/composer && \
    chmod +x /usr/bin/composer /run.sh && \
    cd /var/www/localhost/ && \
    composer install

VOLUME [ "/var/lib/mysql" ]

EXPOSE 80

ENTRYPOINT ["/run.sh"]
CMD ["/usr/sbin/sshd","-D"]
