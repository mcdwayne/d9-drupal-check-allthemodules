#!/usr/bin/env bash
# Note: This should run inside docker.

# Install composer dependencies and the site.
composer config extra.patches-file "composer.patches.json" \
  && composer config discard-changes true \
  && composer require drupal/parade:2.x-dev \
  && composer install -n \
  && cd web \
  && drush site-install --site-name="Test" --account-pass=123 --db-url=mysql://drupal:drupal@mariadb/drupal standard -y \
  && drush en parade_demo -y \
  && drush cr
