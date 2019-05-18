SUMMARY
-------

Integrates Instamojo Payment Gateway with Drupal Commerce.

REQUIREMENTS
------------

This module depends on:

1. [Commerce 2.x](https://www.drupal.org/project/commerce)
2. [instamojo-php](https://github.com/Instamojo/instamojo-php)


INSTALLATION
------------

Download this module using composer to download all its dependencies:

`composer require drupal/commerce_instamojo`

**ALTERNATIVE METHOD**

Use [Ludwig](https://www.drupal.org/project/ludwig) module to download dependencies.

CONFIGURATION
-------------

1. Add "Instamojo (Redirect to Instamojo)" payment gateway 
under "admin/commerce/config/payment-gateways/add".
2. Enter the required credentials.
