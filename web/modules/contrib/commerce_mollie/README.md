# Mollie integration for Drupal Commerce 2

* Provides payment method for Drupal Commerce 2 for Drupal 8 (not Drupal Payment)
* @see https://www.drupal.org/project/commerce
* @see https://github.com/mollie/mollie-api-php

# Payment methods that are integrated

* Default: Commerce Checkout

# Installation

* ```$ composer require drupal/commerce_mollie```
* ```$ composer update drupal/commerce_mollie --with-dependencies```
* Enable 'commerce mollie' in your Drupal installation
* Config payment-gateway @ /admin/commerce/config/payment-gateways
* As order-workflow choose 'Default, with validation' @ /admin/commerce/config/order-types/default/edit
