Introduction
------------

The Commerce TaxJar module provides integration with the TaxJar automated sales tax calculation, reporting, and filing platform for Drupal Commerce.

 * For a full description of the module, visit the [project page on drupal.org](https://drupal.org/project/commerce_taxjar).

 * To submit bug reports and feature suggestions, or to track changes:
   [drupal.org/project/issues/commerce_taxjar](https://drupal.org/project/issues/commerce_taxjar)

 * For more information about TaxJar, visit the website: [taxjar.com](https://www.taxjar.com)


Requirements
------------

 * Drupal Commerce version 2.8 or higher.


Installation
------------

 * Installation via composer is recommended, use: `composer require drupal/commerce_taxjar`

 * Once the module is installed, you will need to setup and configure the TaxJar sales tax type
   in Drupal Commerce (see configuration below).


Configuration
-------------

 * Navigate to Administration » Commerce » Configuration » Tax Types (/admin/commerce/config/tax-types).

 * Click "Add Tax Type"

 * On the new tax type form, select the TaxJar plugin and enter your TaxJar API token.

 * Once the new tax type is saved, the module will automatically fetch and download the available
   product tax categories from the TaxJar service. These will be saved in the "TaxJar Categories"
   taxonomy vocabulary, and can be referenced from any commerce product variations which fall under
   a special tax category.

