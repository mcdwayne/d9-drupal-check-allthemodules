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
Commerce Replace Order allows end user to either replace the previous order 
completely or a line item from the previous order.

This module allows to Replace customer order just by creating an simple link or
button.

i.e. 
  1. Link can be created as "/user/{user_id}/orders/{order_id}/replace-order" 
      for replacing the previous order completely where
        {user_id} - the current logged in user id.
        {order_id} - the order id which has to be replaced again.

  2. Link can be created as 
    "/user/{user_id}/orders/{order_id}/{order_item_id}/replace-order" 
    for replacing a line item from the previous order where
      {user_id} - the current logged in user id.
      {order_id} - the order id from which a line item has to be reordered.
      {order_item_id} - order line item id which has to be reordered.

Order of same user will be allowed to repeat. Order of another user will not be
added to cart by different customer.


REQUIREMENTS
------------
This module requires the following modules:

 * Commerce (https://www.drupal.org/project/commerce)
 * commerce_cart (A sub-module which comes with commerce module).
 * commerce_order (A sub-module which comes with commerce module).


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. Visit:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
  for further information.


CONFIGURATION
-------------
 * Configure user permissions in Administration » People » Permissions:

  - commerce Replace order admin access
    The top-level administration categories require this permission to be
    accessible. Users in roles with the "commerce replace order admin access"
    permission will control the visibility of commerce replace order wheather 
    mail has to be sent while the order which the user has tried to replace and 
    that order is not in stock.

  - view own commerce_order
    Users in roles with the "view own commerce_order" permission will be able to
    use the Replace commerce order functionality.


TROUBLESHOOTING
---------------
 * If the user is unable to use the functionality, check the following:
  - Are the "view own commerce_order" permissions enabled for the appropriate
    roles?


MAINTAINERS
-----------

Current maintainers:
 * Vignesh Valliappan (vigneshvalliappan) - 
    https://www.drupal.org/u/vigneshvalliappan
