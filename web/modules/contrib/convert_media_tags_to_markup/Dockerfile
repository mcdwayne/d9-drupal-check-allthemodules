FROM dcycle/drupal:8

# Make sure opcache is disabled during development so that our changes
# to PHP are reflected immediately.
RUN echo 'opcache.enable=0' >> /usr/local/etc/php/php.ini

# Download contrib modules
RUN drush dl devel -y

EXPOSE 80
