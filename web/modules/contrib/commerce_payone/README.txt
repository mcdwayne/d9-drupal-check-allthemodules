Commerce Payone module

INTRODUCTION
------------
This module integrates the German PAYONE Payment Provider (https://www.payone.de/en/)
with Drupal Commerce 2.x (D8) to accept SEPA direct debit and credit card (also 3-D secure) payments.

No external libraries required as PHP library from Payone has been marked as
"out of date" by PAYONE Technical Support.

Currently supports the following payment methods from PAYONE:
* Credit Card
* SEPA direct debit


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core,
  - Commerce Payment (and its dependencies);
* Payone Merchant account (https://www.payone.com/en)

INSTALLATION
-----------

composer require "drupal/commerce_payone"


CONFIGURATION
-------------
* Create new Payone payment gateway
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Payone-specific settings available:

  - Merchant ID
  - Portal ID
  - Sub-Account ID
  - PAYONE Key

  Use the API credentials provided by your Payone merchant account.

* To enable 3-D Secure checking for credit card payments, please activate
  3-D Secure check from PAYONE Merchant Interface.


HOW IT WORKS
------------
* General considerations:
  - The store owner must have a Payone merchant account.
  - Customers should have a valid credit card (Credit card payments) or SEPA/IBAN

  Payone provides several dummy credit card numbers for testing. Please
  request them from Payone Technical Support (tech.support@bspayone.com)

* Credit card payments:
  - Checkout workflow
    It follows the Drupal Commerce Credit Card workflow.
    The customer should enter his/her credit card data
    or select one of the credit cards saved with Payone
    from a previous order.

  - Payment Terminal
    The store owner can Void, Capture and Refund the Payone payments.


TROUBLESHOOTING
---------------
* No troubleshooting pending for now.


KNOWN ISSUES
------------


MAINTAINERS
-----------
This project has been developed by:
nikolai@kommune3.org (www.kommune3.org)
