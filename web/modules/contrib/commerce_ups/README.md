COMMERCE UPS
============

Provides UPS shipping functionality for Drupal Commerce, based heavily on the
work done by [Commerce Fedex](https://github.com/bmcclure/drupal-commerce_fedex).


DEVELOPMENT SETUP
-----------------

1. Use [Composer](https://getcomposer.org/) to get Commerce UPS with all
dependencies: `composer require drupal/commerce_ups`

2. Enable module.

3. Go to /admin/commerce/config/shipping-methods/add:
  * Select 'UPS' as the Plugin
  * Enter the UPS API details
  * Select a default package type
  * Select all the shipping services that should be enabled
  * Fill out any of the optional configs and save configuration
