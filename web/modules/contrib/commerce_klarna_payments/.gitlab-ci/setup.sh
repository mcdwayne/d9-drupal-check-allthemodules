#!/bin/bash
export BASE_DIR=$HOME
export PATH="$PATH:$HOME/.composer/vendor/bin"

function ensure_packages {
  if ! dpkg -s $1 >/dev/null 2>&1; then
    sudo apt-get update -yqq
    sudo apt-get install $1 -yqq
    ensure_php_extensions
  fi
}

function ensure_php_extensions {
  docker-php-ext-install gd pdo_mysql zip
}

function ensure_composer {
  COMPOSER_PATH=$BASE_DIR/composer

  if [ -x "$(command -v composer)" ]; then
    return
  fi

  if [ -f $COMPOSER_PATH ]; then
    return
  fi
  curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  mv composer.phar $COMPOSER_PATH
}

function ensure_drush {
  ensure_composer
  DRUSH_PATH=~/.composer/vendor/bin/drush

  if [ -x "$(command -v drush)" ]; then
    return
  fi

  if [ -f $DRUSH_PATH ]; then
    return
  fi
  composer global require --no-interaction "drush/drush:9.0.*"
}

ensure_packages git zip libpng-dev mysql-client
ensure_drush

cd $BASE_DIR
git clone --depth 1 --branch "$DRUPAL_CORE" http://git.drupal.org/project/drupal.git
cd drupal

# Add drupal composer repositories.
composer config repositories.0 path $CI_PROJECT_DIR
composer config repositories.1 composer https://packages.drupal.org/8
composer global require "hirak/prestissimo"
composer install
composer run-script drupal-phpunit-upgrade
php -d sendmail_path=$(which true); drush --yes -v site-install $DRUPAL_INSTALL_PROFILE --db-url="$SIMPLETEST_DB"
mkdir -p modules/custom
# Symlink module to modules/custom folder.
ln -s $CI_PROJECT_DIR $BASE_DIR/drupal/modules/custom
# Install composer-merge-plugin and install dependencies.
composer require wikimedia/composer-merge-plugin
composer config extra.merge-plugin.require "modules/custom/*/composer.json"
composer update --lock

drush en simpletest $DRUPAL_MODULE_NAME -y

drush runserver http://127.0.0.1:8080 2>&1 &
