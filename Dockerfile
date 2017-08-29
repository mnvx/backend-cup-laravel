FROM ubuntu:16.04
MAINTAINER Nikolay Matiushenkov <mnvx@yandex.ru>

RUN mkdir /work && chmod uao+rwx /work && chown www-data:www-data /work

# Set correct environment variables
ENV HOME /work
ENV DEBIAN_FRONTEND noninteractive

# Install required packages
RUN apt-key adv --keyserver keyserver.ubuntu.com --recv-keys E5267A6C C300EE8C && \
	echo 'deb http://ppa.launchpad.net/ondrej/php/ubuntu xenial main' > /etc/apt/sources.list.d/ondrej-php7.list && \
	echo 'deb http://ppa.launchpad.net/nginx/development/ubuntu xenial main' > /etc/apt/sources.list.d/nginx.list && \
	apt-get update && apt-get install -y \
		curl \
		libcurl3 \
		libcurl3-dev \
		php7.1-fpm \
		php7.1-cli \
		php7.1-mcrypt \
		php7.1-curl \
		php7.1-opcache \
		php7.1-mbstring \
		php7.1-zip \
		php7.1-xml \
		sudo \
		git \
		mc

RUN echo "deb http://repo.yandex.ru/clickhouse/xenial stable main" >> /etc/apt/sources.list.d/clickhouse.list && \
    apt-key adv --keyserver keyserver.ubuntu.com --recv E0C56BD4 && \
    apt-get update && \
    apt-get install -y clickhouse-client clickhouse-server-common

# install composer
# ----------------
#RUN curl -sS https://getcomposer.org/installer | php7.1 \
# && mv composer.phar /usr/local/bin/composer \
# && printf "\nPATH=\"~/.composer/vendor/bin:\$PATH\"\n" | tee -a ~/.bashrc

RUN sed -i 's/;opcache.enable=0/opcache.enable=1/g' /etc/php/7.1/fpm/php.ini && \
	sed -i 's/pm.start_servers = 2/pm.start_servers = 4/g' /etc/php/7.1/fpm/pool.d/www.conf && \
	sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 4/g' /etc/php/7.1/fpm/pool.d/www.conf && \
	sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 4/g' /etc/php/7.1/fpm/pool.d/www.conf && \
	sed -i -e "s/;daemonize\s*=\s*yes/daemonize = no/g" /etc/php/7.1/fpm/php-fpm.conf && \
	apt-get -yq autoremove --purge && \
	apt-get clean && \
	rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN apt-get update && apt-get install -y php7.1-cgi
RUN sed -i 's/disable_functions = .*/disable_functions =/g' /etc/php/7.1/cgi/php.ini

RUN sed -i 's/::1/127.0.0.1/g' /etc/clickhouse-server/config.xml && \
    sed -i 's/::1/127.0.0.1/g' /etc/clickhouse-server/users.xml

RUN apt-get install -y redis-server

RUN mkdir /var/www

#RUN sudo -u www-data git clone https://github.com/mnvx/backend-cup-laravel /var/www/cup-backend
#RUN sudo -u www-data composer install --working-dir="/var/www/cup-backend"
ADD ./ /var/www/cup-backend
#RUN sudo -u www-data composer update --working-dir="/var/www/cup-backend"

RUN cp /var/www/cup-backend/.env.example /var/www/cup-backend/.env
RUN php /var/www/cup-backend/artisan key:generate

RUN service clickhouse-server start && \
    #clickhouse-client -q 'CREATE DATABASE cup;' && \
    php /var/www/cup-backend/artisan cup:migrations && \
    service clickhouse-server stop

# Expose volumes and ports
EXPOSE 80

ADD ./install/data.zip /tmp/data/data.zip

CMD service redis-server start ; \
    service clickhouse-server start ; \
    sh /var/www/cup-backend/background.sh ; \
    cd /var/www/cup-backend ; \
    php ./vendor/bin/ppm start --bootstrap=laravel --port=80 --workers=4 --host=[::] --logging=0 --debug=0
