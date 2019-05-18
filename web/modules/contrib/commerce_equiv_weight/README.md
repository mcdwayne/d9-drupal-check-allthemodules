CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Commerce Equivalency Weight module gives store managers the ability to add 
an "equivalency weight" field to product variations, orders and order items to 
track.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/commerce_equiv_weight

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/commerce_equiv_weight

REQUIREMENTS
------------

Commerce Equivalency Weight requires the following modules:

 * Commerce (https://drupal.org/project/commerce)
 * Commerce Checkout (https://drupal.org/project/commerce)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------
 
 * Configure the equivalency weight settings at Commerce » Configuration » 
 Orders » Settings:

   - Maximum equivalency weight:

     The maximum equivalency weight allowed in a cart to be allowed to checkout.

SETUP
-----

 * By default, the module will add the `commerce_equiv_weight` field to the
 default `product variation`, `order item` and `order` types. These can be added
 to any of these entities by adding the "Has equivalency weight" trait.
 * Set the maximum equivalency weight for orders at 
 `admin/commerce/config/orders/settings`.
 * When creating/editing a product variation with the equivalency weight field,
 you can manage the equivalency weight.

MAINTAINERS
-----------

Current maintainers:
 * Jace Bennest - [thejacer87](https://drupal.org/user/3225829)
 * Dimitris Bozelos - [krystalcode](https://drupal.org/user/2392706)

This project has been sponsored by:
 * Government of Yukon
 * Acro Media Inc.
