README file for Commerce PAYMILL

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration
* How it works
* Troubleshooting
* Maintainers

INTRODUCTION
------------
This project integrates PAYMILL online payments into
the Drupal Commerce payment and checkout systems.
https://www.paymill.com/en/integration
* For a full description of the module, visit the project page:
  https://www.drupal.org/project/commerce_paymill
* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/commerce_paymill


REQUIREMENTS
------------
This module requires the following:
* Submodules of Drupal Commerce package (https://drupal.org/project/commerce)
  - Commerce core,
  - Commerce Payment (and its dependencies);
* PAYMILL-PHP Library (https://github.com/paymill/paymill-php);
* PAYMILL Merchant account (https://app.paymill.com).


INSTALLATION
------------
* This module needs to be installed via Composer, which will download
the required libraries.
composer require "drupal/commerce_paymill"
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies

CONFIGURATION
-------------
* Create new Paymill payment gateway
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Settings available:
  - Private key;
  - Public key.
  All those API credentials are provided by the Paymill merchant account. It is
  recommended to enter test credentials and then override these with live
  credentials in settings.php. This way live credentials will not be exported to code.


HOW IT WORKS
------------

* General considerations:
  - Shop owner must have an PAYMILL merchant account
    Sign up
    https://app.paymill.com/user/register
  - Customer should have a valid credit card.

* Checkout workflow:
  It follows the Drupal Commerce Credit Card workflow.
  The customer should enter its credit card data
  or to select one of the existing PAYMILL payment methods.

* Payment Terminal
  The store owners can Void, Capture and Refund the PAYMILL payments.


TROUBLESHOOTING
---------------
* No troubleshooting pending for now.


MAINTAINERS
-----------
Current maintainers:
* Tavi Toporjinschi (vasike) - https://www.drupal.org/u/vasike

This project has been developed by:
* Commerce Guys by Actualys
  Visit https://commerceguys.fr/ for more information.
