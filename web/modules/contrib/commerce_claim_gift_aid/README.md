Commerce claim gift aid
=======

Adds the ability for shops to collect gift aid from eligible
 gift aid order items.
 
 For more information see the drupal.org project page at:
 https://www.drupal.org/project/commerce_claim_gift_aid
 
 To clone the repository visit the following page:
 https://www.drupal.org/project/commerce_claim_gift_aid/git-instructions

REQUIREMENTS
-------------
1. Commerce 2.0 and the commerce order module.
2. You also need an understanding of how Commerce 2.0 works

INSTALLATION
-------------
To use this module you have to download it using composer and then install it.

   ```sh
    composer require "drupal/commerce_claim_gift_aid"
   ```
USAGE
-------------

* Select whether an order item type is eligible for gift aid using the
following url:
admin/commerce/config/order-item-types

* If that order item is added into the cart then there will be an option for
the user to choose whether you can claim gift aid or not.

 * You can configure checkout flows using the following url if you want to
control where the question appears:
admin/commerce/config/checkout-flows

 * You should edit the gift aid text that appears using the following url:
admin/config/commerce-claim-gift-aid

 * You can use views to build a list of orders where a user has allowed you
to claim gift aid.

CREDITS
-------------
Maintainer and developer:**tresti88**

Development sponsored by:**One**

For professional support and development services contact:
**michaeltrestianu@gmail.com**
