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
		nginx \
		php7.0-fpm \
		php7.0-cli \
		php7.0-gd \
		php7.0-mcrypt \
		php7.0-sqlite \
		php7.0-curl \
		php7.0-opcache \
		php7.0-mbstring \
		php7.0-zip \
		php7.0-xml \
		php-mysql \
		redis-server \
		sudo \
		git \
		apt-utils \
		mc

# install composer
# ----------------
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer \
 && printf "\nPATH=\"~/.composer/vendor/bin:\$PATH\"\n" | tee -a ~/.bashrc

COPY install/nginx-default /etc/nginx/sites-available/default

# Configure PHP, Nginx, Redis, and clean up
RUN sed -i 's/;opcache.enable=0/opcache.enable=1/g' /etc/php/7.0/fpm/php.ini && \
	sed -i 's/;opcache.fast_shutdown=0/opcache.fast_shutdown=1/g' /etc/php/7.0/fpm/php.ini && \
	sed -i 's/;opcache.enable_file_override=0/opcache.enable_file_override=1/g' /etc/php/7.0/fpm/php.ini && \
	sed -i 's/;opcache.revalidate_path=0/opcache.revalidate_path=1/g' /etc/php/7.0/fpm/php.ini && \
	sed -i 's/;opcache.save_comments=1/opcache.save_comments=1/g' /etc/php/7.0/fpm/php.ini && \
	sed -i 's/;opcache.revalidate_freq=2/opcache.revalidate_freq=60/g' /etc/php/7.0/fpm/php.ini && \
	sed -i 's/pm.max_children = 5/pm.max_children = 12/g' /etc/php/7.0/fpm/pool.d/www.conf && \
	sed -i 's/pm.start_servers = 2/pm.start_servers = 4/g' /etc/php/7.0/fpm/pool.d/www.conf && \
	sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 4/g' /etc/php/7.0/fpm/pool.d/www.conf && \
	sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 8/g' /etc/php/7.0/fpm/pool.d/www.conf && \
	echo "\ndaemon off;" >> /etc/nginx/nginx.conf && \
	sed -i -e "s/;daemonize\s*=\s*yes/daemonize = no/g" /etc/php/7.0/fpm/php-fpm.conf && \
	sed -i 's/^daemonize yes/daemonize no/' /etc/redis/redis.conf && \
	sed -i 's/^# maxmemory <bytes>/maxmemory 32mb/' /etc/redis/redis.conf && \
	sed -i 's/^# maxmemory-policy volatile-lru/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf && \
	apt-get -yq autoremove --purge && \
	apt-get clean && \
	rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN chown www-data:www-data /var/www
RUN sudo -u www-data git clone https://github.com/mnvx/backend-cup-laravel /var/www/cup-backend
RUN sudo -u www-data composer install --working-dir="/var/www/cup-backend"
RUN cp /var/www/cup-backend/.env.example /var/www/cup-backend/.env
RUN php /var/www/cup-backend/artisan key:generate

# Expose volumes and ports
EXPOSE 80

CMD service php7.0-fpm start && service nginx start
