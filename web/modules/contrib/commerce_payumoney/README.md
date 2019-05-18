INTRODUCTION
------------

This project integrates PayUmoney into the http://drupal.org/project/commerce 
payment and checkout systems.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/commerce_payumoney.
 
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/commerce_payumoney.


REQUIREMENTS
------------

This module requires the following modules:
 * Commerce2 (https://drupal.org/project/commerce)
http://docs.drupalcommerce.org/v2/getting-started/install.html

CONFIGURATION
-------------
 * Please Add "PayUmoney Redirect" Payment gateway under 
   "admin/commerce/config/payment-gateways/add"
 * Add the required credentials .
 * Please write machine name of available Profile to get required information for API in Profile textbox. 
   Default is "customer".
 * Selected profile must have added "Address" entity.
 * Please Add New phone field in Select Profile with name "field_phone"
