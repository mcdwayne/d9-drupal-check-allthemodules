INTRODUCTION
------------

This module adds a new field formatter to show at the same time:
- The original price of the variation.
- The price with the events applied.
- The difference in price in percentage.

The initial idea arose in a project in which it was necessary to show the percentage of discount both in the lists of products and in the pages of detail.

REQUIREMENTS
------------

This module requires the following modules:
* Commece (https://www.drupal.org/project/commerce)

INSTALLATION
------------

* Install as you would normally install a Drupal module contributed.

CONFIGURATION
-------------

* Go to the dispaly management page of the fields of product variations:
  Administration >> Commerce >> Configuration >> Product variation types >> Edit Default (/admin/commerce/config/product-variation-types/default/edit/display)

* Select "Price Difference Formatter" as the format of the price field and configure the options
