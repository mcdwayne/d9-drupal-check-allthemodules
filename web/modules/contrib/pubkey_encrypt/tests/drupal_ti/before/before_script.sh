#!/bin/bash

# Add an optional statement to see that this is running in Travis CI.
echo "running drupal_ti/before/before_script.sh"

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# The first time this is run, it will install Drupal.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Manually clone the dependencies
cd "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
git clone --branch 8.x-3.x https://git.drupal.org/project/encrypt.git
git clone --branch 8.x-1.x https://git.drupal.org/project/key.git
git clone --branch 8.x-1.x https://git.drupal.org/project/encrypt_seclib.git
cd "$DRUPAL_TI_DRUPAL_DIR"
composer require phpseclib/phpseclib:"^2.0.0"
