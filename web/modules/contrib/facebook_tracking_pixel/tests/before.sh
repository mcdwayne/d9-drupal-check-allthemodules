#!/bin/bash

cd $DRUPAL_TI_DRUPAL_DIR
wget https://www.drupal.org/files/issues/drupal-session_destroy_return_bool-2460833-30.patch
git apply drupal-session_destroy_return_bool-2460833-30.patch
echo "Status 0"
cd profiles/standard/translations
wget http://ftp.drupal.org/files/translations/7.x/drupal/drupal-7.43.es.po
ls ./
cd $DRUPAL_TI_DRUPAL_DIR
pwd
echo "Start Drush Downloads"
drush dl ctools-7.x-1.x-dev
drush dl commerce-7.x-1.11
echo "Start Drush Commerce Enable"
drush en -y commerce_tax, commerce_price, commerce_cart, commerce_product, commerce_order, commerce