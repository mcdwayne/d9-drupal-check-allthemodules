CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows you to add rubrication of products in the shopping cart.
This could be useful, for example, for restaraunts, when dishes in shopping cart can be easily classified by their kind (soups, salads, main dishes, drinks etc.).

 * For a full description of the module visit:
   https://www.drupal.org/project/commerce_cart_categories

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/commerce_cart_categories


REQUIREMENTS
------------

This module requires Commerce and Commerce Cart modules.


INSTALLATION
------------

 * Install the Categories for Commerce Cart module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Create taxonomy vocabulary with the list of desired categories for your shopping cart.
    3. Add taxonomy reference field for your commerce products and associate it with this vocabulary.
    4. Specify category values for each of your products. If particular product does not have the category, 
       in the shopping cart it will be displayed without category, and located above categorized products.
    5. Navigate to Administration > Commerce > Configuration > Products > Cart categories page, 
       and select the name of taxonomy reference field you've created at step 3.
    6. Save configuration.


MAINTAINERS
-----------

 * Ievgen Melezhyk (melezhik_ea) - https://www.drupal.org/u/melezhik_ea
