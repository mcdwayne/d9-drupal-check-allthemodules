Commerce USPS
=================

Provides USPS shipping rates for Drupal Commerce.

## Development Setup


1. Use [Composer](https://getcomposer.org/) to get Commerce USPS
 with all dependencies: `composer require drupal/commerce_usps`

2. Enable module.

4. Go to /admin/commerce/config/shipping-methods/add:
  - Select 'USPS' as the Plugin
  - Enter the USPS API details
  - Select a default package type
  - Select all the shipping services that should be disabled
  - Fill out any of the optional configs and save configuration
