CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------
Commerce Repeat Order allows end user to repeat there order again and again.

This module allows to repeat customer order just by creating an simple link or
button.

i.e. Link can be created as "commerce-repeat-order/{commerce_order}" where
"commerce_order" is commerce order id which needs to be passed dynamically to
repeat that order.

Order of same user will be allowed to repeat. Order of another user will not be
added to cart by different customer.


REQUIREMENTS
------------
This module requires the following modules:

 * Commerce (https://www.drupal.org/project/commerce)
 * commerce_cart (A sub-module which comes with commerce module).


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. Visit:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
  for further information.


CONFIGURATION
-------------
 * Configure user permissions in Administration » People » Permissions:

  - commerce repeat order admin access
    The top-level administration categories require this permission to be
    accessible. Users in roles with the "commerce repeat order admin access"
    permission will control the visibility of commerce repeat order message and
    while repeating the order existing cart should be used or new cart.

  - view own commerce_order
    Users in roles with the "view own commerce_order" permission will be able to
    use the repeat commerce order functionality.


TROUBLESHOOTING
---------------
 * If the user is unable to use the functionality, check the following:
  - Are the "view own commerce_order" permissions enabled for the appropriate
    roles?


MAINTAINERS
-----------

Current maintainers:
 * Pratik Mehta (pratik.mehta19) - https://www.drupal.org/u/pratikmehta19
