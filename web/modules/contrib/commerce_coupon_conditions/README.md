CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module is extending Commerce 2 promotion coupon to support
commerce conditions plugin in Drupal 8.

Commerce promotion module have conditions, but in some cases is more useful
to have one discount with multiple coupons attached,
and that each coupon has own restrictions.

It can be more user-friendly & less work for commerce manager
to create one discount and multiple coupons with restrictions then reverse.

Module is extending:
 * Drupal entity commerce_promotion_coupon: `\Drupal\commerce_coupon_conditions\Entity\Coupon`
 * Commerce coupon list builder to provide more information: `\Drupal\commerce_coupon_conditions\CouponConditionsListBuilder`


REQUIREMENTS
------------

This module requires Drupal Commerce 2 and its submodule promotion.


INSTALLATION
------------

Install the Commerce Coupon Conditions module as you would normally install
any Drupal contrib module.
Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

Module does not have any configuration.


MAINTAINERS
-----------

The 8.x-1.x branch was created by:

 * Valentino Medimorec (valic) - https://www.drupal.org/u/valic

This module was created and sponsored by Foreo,
Swedish multi-national beauty brand.

 * Foreo - https://www.foreo.com/
